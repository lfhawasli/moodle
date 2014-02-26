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

/*
 * Class to set/get data from Registrar tables, a stored procedure.
 *
 * @package    local_ucla
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/local/ucla_syllabus/locallib.php');

/**
 * Handles setting/getting data from Registrar tables.
 *
 * NOTE: Cannot use prepared statements, because the ODBC library that we use to
 * connect to the Registrar does not support it. So need to carefully construct
 * raw SQl to communicate to the Registrar.
 *
 * @package    local_ucla
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ucla_regsender {
    /**
     * Constants to return when set_syllabus_links is called.
     */
    const FAILED = -1;
    const NOUPDATE = 0;
    const PARTIALUPDATE = 1;
    const SUCCESS = 2;


    /**
     * Database connection to registrar.
     *
     * @var ADOConnection
     */
    private $adodb = null;

    /**
     * The syllabus table name to update at the Registrar. Set in the contructor
     * after determining what environment we are running.
     *
     * @var string
     */
    private $syllabustable = null;

    /**
     * Types of syllabi we are sending.
     *
     * @var array
     */
    public static $syllabustypes = array('public', 'private', 'protect');

    /**
     * Constructor.
     */
    public function __construct() {
        ucla_require_registrar();
        $this->syllabustable = get_config('local_ucla', 'regsyllabustable');
    }

    /**
     * Closes connection to the registrar.
     */
    public function close_regconnection() {
        global $CFG;
        if (empty($this->adodb)) {
            // Already closed.
            return;
        }
        $this->adodb->Close();
        unset($this->adodb);

        // The UCLA registrar_query object stores a "cached" copy of the
        // connection in $CFG, we need to remove it or it will mess up
        // subsequent tests/calls.
        unset($CFG->ucla_extdb_registrar_connection);
    }

    /**
     * Returns an array of entries, with a limit of $limit, from the Registrar
     * syllabus table.
     *
     * @param type $limit   Maximum syllabus entries to return. Default is 10.
     * @return array        Returns null if cannot connect to the Registrar.
     */
    public function get_recent_syllabus_links($limit = 10) {
        $adodb = $this->open_regconnection();
        if (empty($adodb)) {
            return null;
        }

        // Do some sanity checking.
        $limit = intval($limit);
        if ($limit <= 0) {
            $limit = 10;
        }

        $sql = "SELECT  *
                FROM    $this->syllabustable
                WHERE   1=1
                ORDER BY    update_timestamp DESC";

        $rs = $adodb->SelectLimit($sql, $limit);

        return $rs->GetAll();
    }

    /**
     * For given classinfo, will return a syllabus link.
     *
     * @throws registrar_query_exception    If cannot connect to registrar.
     *
     * @param string $term
     * @param string $subjarea
     * @param string $crsidx    Unformatted catalog number.
     * @param string $classidx  Unformatted section number.
     * @return array            Record from the Registrar syllabus table.
     */
    public function get_syllabus_link($term, $subjarea, $crsidx, $classidx) {
        $adodb = $this->open_regconnection();

        $sql = "SELECT  *
                FROM    $this->syllabustable
                WHERE   term_cd='$term' AND
                        subj_area_cd='$subjarea' AND
                        crs_catlg_no='$crsidx' AND
                        sect_no='$classidx'";
        return $adodb->GetRow($sql);
    }

    /**
     * For given courseid, will return list of syllabus links indexed by
     * term/srs.
     *
     * @throws registrar_query_exception    If cannot connect to registrar.
     * 
     * @param int $courseid
     * @return array            Array of records from the Registrar indexed by
     *                          term and srs. Returns false if course is not a
     *                          Registrar course.
     */
    public function get_syllabus_links($courseid) {
        $retval = array();

        // Get all related fields to build key to query records.
        $courseinfos = ucla_get_course_info($courseid);
        if (empty($courseinfos)) {
            // Not a registrar course.
            return false;
        }

        foreach ($courseinfos as $courseinfo) {
            $result = $this->get_syllabus_link(
                            $courseinfo->term,
                            $courseinfo->subj_area,
                            $courseinfo->crsidx,
                            $courseinfo->classidx);
            $retval[$courseinfo->term][$courseinfo->srs] = $result;
        }

        return $retval;
    }

    /**
     * Ensures that a connection to the registrar is opened.
     *
     * @throws registrar_query_exception    If cannot connect to registrar.
     *
     * @param boolean $debug    Optional.
     * @return ADOConnection    Returns null if connection cannot be opened.
     */
    protected function open_regconnection($debug = false) {
        if (empty($this->adodb)) {            
            $this->adodb = registrar_query::open_registrar_connection();
        }        
        $this->adodb->debug = $debug;
        return $this->adodb;
    }

    /**
     * Looks up the type of syllabi uploaded for a course and sends the 
     * corresponding links to the Registrar.
     *
     * @param int $courseid
     * @param progress_trace $trace Optional. If passed, will send logging data,
     *                              else will send messages to null.
     */
    public function push_course_links($courseid, $trace=null) {
        global $DB;
        $retval = false;
        if (empty($trace)) {
            $trace = new null_progress_trace();
        }

        // Create empty array of syllabus links. Then set them if there is a
        // syllabus for that type.
        $links = array();
        foreach (self::$syllabustypes as $type) {
            $links[$type] = '';
        }

        $courselink = (new moodle_url('/local/ucla_syllabus/index.php',
                    array('id' => $courseid)))->out();

        // Get syllabi for course. Do not use ucla_syllabus_manager, since it
        // has a lot of overhead.
        $syllabustypes = $DB->get_fieldset_select('ucla_syllabus', 'access_type',
                'courseid = ?', array($courseid));

        $setlinks = array();
        if (!empty($syllabustypes)) {
            foreach ($syllabustypes as $type) {
                switch ($type) {
                    case UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC:
                        $links['public'] = $courselink;
                        $setlinks[] = 'public';
                        break;
                    case UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN:
                        $links['protect'] = $courselink;
                        $setlinks[] = 'protect';
                        break;
                    case UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE:
                        $links['private'] = $courselink;
                        $setlinks[] = 'private';
                        break;
                }
            }
        }

        if (empty($setlinks)) {
            $trace->output("No syllabi found, clearing links", 1);
        } else {
            $trace->output(sprintf("Setting links for: %s, clearing others", implode(', ', $setlinks)), 1);
        }

        $result = $this->set_syllabus_links($courseid, $links);
        if ($result == local_ucla_regsender::FAILED) {
            $trace->output("ERROR! Could not set links for course id $courseid; Aborting", 1);
        } else if ($result == local_ucla_regsender::NOUPDATE) {
            $trace->output("Syllabi links already set, no changes", 1);
            $retval = true;
        } else if ($result == local_ucla_regsender::PARTIALUPDATE) {
            $trace->output("Some syllabi links already set, some changes", 1);
            $retval = true;
        } else if ($result == local_ucla_regsender::SUCCESS) {
            $trace->output("All syllabi links set successfully", 1);
            $retval = true;
        } else {
            $trace->output("ERROR! Unknown return code; Aborting", 1);
        }

        return $retval;
    }

    /**
     * For given classinfo, will set a syllabus link.
     *
     * @throws registrar_query_exception    If cannot connect to registrar.
     *
     * @param string $term
     * @param string $subjarea
     * @param string $crsidx    Unformatted catalog number.
     * @param string $classidx  Unformatted section number.
     * @param array $links      An array with the following possible keys:
     *                          public, private, and protect.
     * @return int          Return code   | Explaination:
     *                      FAILED        | Error, either could not connect or
     *                                      course is not a Registrar course.
     *                      NOUPDATE      | Entries at Registrar are the same as
     *                                      being sent or belong to another
     *                                      server, so no changes made.
     *                      SUCCESS       | Updated links at Registrar.
     */
    public function set_syllabus_link($term, $subjarea, $crsidx, $classidx, $links) {
        global $CFG;
        $adodb = $this->open_regconnection();

        // Make sure that $links parameter has at least 1 type of syllabus.
        $linkparams = array();
        foreach (self::$syllabustypes as $syllabustype) {
            if (isset($links[$syllabustype])) {
                $linkparams[$syllabustype] = $links[$syllabustype];
            }
        }

        if (empty($linkparams)) {
            // Need to send something to the Registrar.
            return self::FAILED;
        }

        // Prepare column and value arrays to user later when inserting or
        // updating.
        $typecols = array();
        $typevals = array();
        foreach ($linkparams as $type => $link) {
            $typecols[] = $type . '_syllabus_url';
            $typevals[] = $link;
        }

        // First get any existing records.
        $sql = "SELECT  *
                FROM    $this->syllabustable
                WHERE   term_cd='$term' AND
                        subj_area_cd='$subjarea' AND
                        crs_catlg_no='$crsidx' AND
                        sect_no='$classidx'";
        $existing = $adodb->GetRow($sql);

        $result = null;
        if ($existing === false) {
            // Could not do query.
            return self::FAILED;
        } else if (empty($existing)) {
            // Record does not exist, so insert it.
            $sql = "INSERT INTO $this->syllabustable
                    (term_cd, subj_area_cd, crs_catlg_no, sect_no, " .
                     implode(',', $typecols) . ")
                    VALUES
                    ('$term', '$subjarea',
                     '$crsidx', '$classidx'";

            foreach ($typevals as $typeval) {
                $sql .= sprintf(",'%s'", $typeval);
            }

            $sql .= ')';

            $result = $adodb->Execute($sql);
        } else {
            // Record exists, so update it.

            // Add in some sanity checking here. Make sure we are only
            // updating links on the Registrar if they are from the same
            // server, the same as we do with the IEI class links.
            $serverhost = parse_url($CFG->wwwroot, PHP_URL_HOST);
            foreach ($typecols as $index => $typecol) {
                if (empty($existing[$typecol])) {
                    continue;
                }
                $reghost = parse_url($existing[$typecol], PHP_URL_HOST);
                if ($serverhost != $reghost) {
                    // Registrar link is not the same server, so do not
                    // overwrite it.
                    unset($typecols[$index]);
                    unset($typevals[$index]);
                }
            }

            // See if there is anything left to update now.
            if (empty($typecols)) {
                return self::NOUPDATE;
            }

            $sql = "UPDATE $this->syllabustable
                    SET ";

            $firstentry = true;
            foreach ($typecols as $index => $typecol) {
                $firstentry ? $firstentry = false : $sql .= ',';
                $sql .= sprintf(" %s = '%s' ", $typecol, $typevals[$index]);
            }

            $sql .= "WHERE  term_cd='$term' AND
                            subj_area_cd='$subjarea' AND
                            crs_catlg_no='$crsidx' AND
                            sect_no='$classidx'";

            $result = $adodb->Execute($sql);
        }

        if ($result === false) {
            // Some problem happened.
            return self::FAILED;
        }

        if ($adodb->Affected_Rows()) {
            return self::SUCCESS;
        } else {
            // No changed, so must have been an update with no changes.
            return self::NOUPDATE;
        }
    }

    /**
     * For given courseid, will update the appropiate syllabus entries at the
     * Registrar with the the given url for given syllabus type.
     *
     * If no url given for given syllabus type, it will erase whatever the url
     * link is at the Registrar.
     *
     * @param int $courseid
     * @param array $links  An array with the following possible keys:
     *                      public, private, and protect.
     *
     * @return int          Return code   | Explaination:
     *                      FAILED        | Error, either could not connect or
     *                                      course is not a Registrar course.
     *                      NOUPDATE      | Entries at Registrar are the same as
     *                                      being sent or belong to another
     *                                      server, so no changes made.
     *                      PARTIALUPDATE | For cross-listed sections. One or
     *                                      more sections already have the links
     *                                      being passed as set.
     *                      SUCCESS       | Updated links at Registrar.
     */
    public function set_syllabus_links($courseid, $links) {
        // Get all related fields to build key to update records.
        $courseinfos = ucla_get_course_info($courseid);
        if (empty($courseinfos)) {
            // Not a registrar course.
            return self::FAILED;
        }

        // Not all courseinfo entries might bee updated. Need to keep track of
        // partial updates so that we can return the right return codes.
        $hasaffectedrows = false;
        $noupdateoccurred = false;
        foreach ($courseinfos as $courseinfo) {

            $result = $this->set_syllabus_link(
                    $courseinfo->term,
                    $courseinfo->subj_area,
                    $courseinfo->crsidx,
                    $courseinfo->classidx,
                    $links);

            if ($result == self::FAILED) {
                return self::FAILED;
            } else if ($result == self::SUCCESS) {
                $hasaffectedrows = true;
            } else if ($result == self::NOUPDATE) {
                $noupdateoccurred = true;
            }
        }

        if ($hasaffectedrows && $noupdateoccurred) {
            // Some records were updated, but not all.
            return self::PARTIALUPDATE;
        } else if ($hasaffectedrows && !$noupdateoccurred) {
            // Records updated and nothing skipped.
            return self::SUCCESS;
        }

        // If no rows were affected, then no changed occurred.
        return self::NOUPDATE;
    }
}
