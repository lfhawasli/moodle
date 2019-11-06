<?php
// This file is part of the UCLA local plugin for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Registrar Connectivity class.
 *
 * @package     local_ucla
 * @copyright   2012 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

/**
 * Registrar Connectivity class.
 *
 * Essentially a wrapper for a wrapper for ODBC.
 * 
 * @copyright   2012 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class registrar_query {
    /**
     * Holds onto the Registrar connection object.
     *
     * @var ADOConnection
     */
    private $registrarconn = null;

    /**
     * Flags used to indicate keys of return value when you do not want to
     * ignore invalid returns.
     */
    const QUERY_RESULTS = 'good';

    /**
     * Index of key when we want to include bad data.
     */
    const FAILED_OUTPUTS = 'bad';

    /**
     * These are the bad outputs, or outputs that made $this->validate() return
     * false.
     * @var array
     */
    private $badoutputs = array();

    /**
     * Used to determine if trimming of stuff is needed.
     *
     * @var array
     */
    protected $notrim = false;

    /**
     * The default encoding.
     */
    const DEFAULT_ENCODING = 'ISO-8859-1';

    /**
     * Runs given Registrar stored procedure.
     *
     * @throws registrar_query_exception if no stored procedure
     *     wrapper class is found
     *
     * @param string $queryname The name of the stored procedure.
     * @param array $data       The data to pass into stored procedure.
     * @param boolean $filtered        Default true. If false, then will make sure
     *                          result includes bad data
     * @return array
     */
    public static function run_registrar_query($queryname, $data, $filtered=true) {
        $rq = self::get_registrar_query(strtolower($queryname));
        if (!$rq) {
            return false;
        }

        $rt = $rq->retrieve_registrar_info($data, $filtered);

        if ($filtered) {
            return $rt;
        }

        return array(
            self::QUERY_RESULTS => $rt,
            self::FAILED_OUTPUTS => $rq->get_bad_outputs()
        );
    }

    /**
     * This function will utilize the ODBC connection and retrieve data.
     *
     * @param array $drivingdata The data to run a set of queries on.
     * @param boolean $filtered Not used in base class. Passed by run_registrar_query
     *
     * @return Array( Array( ) )
     *     false - indicates bad input
     *     empty array() - indicates good input, but no results
     */
    public function retrieve_registrar_info($drivingdata, $filtered=true) {
        $directdata = array();

        try {
            $dbreg = $this->get_registrar_connection();
        } catch (registrar_query_exception $e) {
            // We want to log errors with Registrar connections.
            // @codingStandardsIgnoreLine
            error_log($e->getMessage());
            return false;
        }
        $qr = $this->remote_call_generate($drivingdata);

        // Let's not fail hard.
        if ($qr === false) {
            debugging('failed to generate query');
            return false;
        }

        $qr = self::db_encode($qr);

        $recset = $dbreg->Execute($qr);

        if ($recset && !$recset->EOF) {
            while ($fields = $recset->FetchRow()) {
                if ($this->validate($fields, $drivingdata)) {
                    $res = $this->clean_row($fields);

                    $key = $this->get_key($res, $drivingdata);
                    if ($key == null) {
                        $directdata[] = $res;
                    } else {
                        $directdata[$key] = $res;
                    }
                } else {
                    // We need to return the malevolent data.
                    $this->badoutputs[] = $fields;
                }
            }
        }

        return $directdata;
    }

    /**
     * Finds the file for the query and creates the query connection
     * object.
     *
     * @param string $queryname
     *
     * @return object
     */
    public static function get_registrar_query($queryname) {
        $classname = 'registrar_' . $queryname;
        if (!class_exists($classname)) {
            $fn = dirname(__FILE__) . "/$classname.class.php";
            if (file_exists($fn)) {
                require_once($fn);
            } else {
                throw new registrar_query_exception(
                    $classname . ' not found'
                );
            }
        }

        if (class_exists($classname)) {
            return new $classname();
        }

        return false;
    }

    /**
     * Since a single query can return multiple results, if we want to
     * allow good results but not bad ones, then we save them here.
     *
     * @return array
     */
    public function get_bad_outputs() {
        return $this->badoutputs;
    }

    /**
     * Clears bad_outputs.
     */
    public function flush_bad_outputs() {
        $this->badoutputs = array();
    }

    /**
     * Returns an index to use for the return data. Default is to not
     * index the results in any way, and have a default integer index.
     *
     * @param array $fields The data to be indexed.
     *
     * @return string       The key to use for the index.
     */
    public function get_key($fields) {
        return null;
    }

    /**
     * Trims all fields and makes the case of the keys to lower case.
     *
     * @param array $fields
     *
     * @return array
     */
    public function clean_row($fields) {
        $new = array_change_key_case($fields, CASE_LOWER);

        $notrim = is_array($this->notrim) ? $this->notrim : array();

        foreach ($new as $k => $v) {
            if (in_array($k, $notrim)) {
                continue;
            }

            $new[$k] = trim($v);
        }

        $new = self::db_decode($new);

        return $new;
    }

    /**
     * Returns the ADOConnection object for registrar connection.
     *
     * Wrapper for 
     * @see registrar_query::open_registrar_connection() Opens a registrar
     *                                                               connection object
     *
     * May change state of object.
     *
     * @return ADOConnection The connection to the registrar.
     */
    public function get_registrar_connection() {
        if ($this->registrarconn == null) {
            $this->registrarconn = $this->open_registrar_connection();
        }

        return $this->registrarconn;
    }

    /**
     * Checks if there is a real connection to the Registrar.
     *
     * Use this function if you want to check the connection without
     * having to deal with a registrar_query object.
     *
     * @return boolean
     */
    static public function has_registrar_connection() {
        $tester = new registrar_tester();
        try {
            $tester->get_registrar_connection();
        } catch (registrar_query_exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Closes the ADOConnection object for Registrar connection.
     *
     * May change the state of object.
     *
     * @return boolean
     */
    public function close_registrar_connection() {
        if ($this->registrarconn == null) {
            return false;
        }

        $this->registrarconn->Close();
        $this->registrarconn = null;

        return true;
    }

    /**
     * This function will be run on every returned Registrar entry.
     * If this function returns false, the entry from the Registrar will
     * not be returned, but will be stored specially.
     *
     * @see retrieve_registrar_info()
     *
     * @param array $new    The row from the Registrar.
     * @param array $old    The row from the driving data.
     *
     * @return boolean  Registrar entries that fail to validate can be accessed
     *                  separately.
     */
    abstract public function validate($new, $old);

    /**
     * This is the function used to generate the stored procedure.
     *
     * @see retrieve_registrar_info()
     *
     * @param array $args   The arguments to be used in generating the
     *                      remote query. It is prefereable to have them indexed
     *                      meaningfully: i.e. 'term', 'subjarea', 'srs'.
     */
    abstract public function remote_call_generate($args);

    /**
     * Create a Registrar connection object.
     *
     * Stolen from enrol/database/lib.php:enrol_database_plugin.db_init()
     * @return ADOConnection
     */
    static public function open_registrar_connection() {
        global $CFG;

        // This will allow us to share connections.
        if (isset($CFG->ucla_extdb_registrar_connection)) {
            return $CFG->ucla_extdb_registrar_connection;
        }

        require_once($CFG->libdir . '/adodb/adodb.inc.php');

        $dbtype = get_config('', 'registrar_dbtype');
        if ($dbtype == '') {
            throw new registrar_query_exception(
                'Registrar DB not set!'
            );
        }

        // Manually coded check for odbc functionality, since moodle doesn't
        // seem to like exceptions.
        if (strpos($dbtype, 'odbc') !== false) {
            if (!function_exists('odbc_exec')) {
                throw new Exception('FATAL ERROR: ODBC not installed!');
            }
        }

        // Connect to the external database.
        $extdb = ADONewConnection($dbtype);
        if (!$extdb) {
            throw new registrar_query_exception(
                'Could not connect to registrar!'
            );
        }

        /* Add '/' to beginning of this line to debug registrar SQL statements
        if ($CFG->debug > 0) {
            $extdb->debug = true;
        }
        //*/

        // If the stored procedures are not working, uncomment this line.
        /*
        $extdb->curmode = SQL_CUR_USE_ODBC;
         */
        if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            // Need to specify database name for PHPunit tests.
            $status = $extdb->Connect(
                get_config('', 'registrar_dbhost'),
                get_config('', 'registrar_dbuser'),
                get_config('', 'registrar_dbpass'),
                get_config('', 'registrar_dbname')
            );
        } else {
            $status = $extdb->Connect(
                get_config('', 'registrar_dbhost'),
                get_config('', 'registrar_dbuser'),
                get_config('', 'registrar_dbpass')
            );
        }

        if ($status == false) {
            throw new registrar_query_exception(
                'registrar connection failed!'
            );
        }

        $extdb->SetFetchMode(ADODB_FETCH_ASSOC);

        $CFG->ucla_extdb_registrar_connection =& $extdb;

        return $extdb;
    }

    /**
     * Go from the utf-8 to the remote db's encoding.
     *
     * @param string $text
     *
     * @return string
     */
    public static function db_encode($text) {
        $dbenc = self::db_coding_check();
        if (!$dbenc) {
            return $text;
        }

        if (is_array($text)) {
            foreach ($text as $k => $value) {
                $text[$k] = self::db_encode($value);
            }
        } else {
            $text = core_text::convert($text, self::DEFAULT_ENCODING, $dbenc);
        }

        return $text;
    }

    /**
     * Come from the remote db's encoding into utf-8.
     *
     * @param string $text
     *
     * @return string
     */
    public static function db_decode($text) {
        $dbenc = self::db_coding_check();
        if (!$dbenc) {
            return $text;
        }

        if (is_array($text)) {
            foreach ($text as $k => $value) {
                $text[$k] = self::db_decode($value);
            }
        } else {
            $text = core_text::convert($text, $dbenc, self::DEFAULT_ENCODING);
        }

        return $text;
    }

    /**
     * Checks if we need to do the en/decoding.
     *
     * @return string
     */
    static public function db_coding_check() {
        $dbenc = get_config('', 'registrar_dbencoding');
        if ($dbenc == self::DEFAULT_ENCODING) {
            return false;
        }

        return $dbenc;
    }
}

/**
 * Extends moodle_exception.
 * 
 * @copyright   2012 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class registrar_query_exception extends moodle_exception {
    // Nothing...
}
