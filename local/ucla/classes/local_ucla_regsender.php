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

/**
 * Handles setting/getting data from Registrar tables.
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
     * For given courseid, will return list of syllabus links indexed by
     * term/srs.
     *
     * @param int $courseid
     * @return array            Array of records from the Registrar indexed by
     *                          term and srs.
     */
    public function get_syllabus_links($courseid) {
        $retval = array();

        $adodb = $this->open_regconnection();

        // Get all related fields to build key to query records.
        $courseinfos = ucla_get_course_info($courseid);
        if (empty($courseinfos)) {
            // Not a registrar course.
            return false;
        }

        foreach ($courseinfos as $courseinfo) {
            // First get any existing records.
            $sql = "SELECT  *
                    FROM    $this->syllabustable
                    WHERE   term_cd=? AND
                            subj_area_cd=? AND
                            crs_catlg_no=? AND
                            sect_no=?";
            // Order matters and must match ? placeholders.
            $params = array($courseinfo->term, $courseinfo->subj_area,
                    $courseinfo->crsidx, $courseinfo->classidx);
            $retval[$courseinfo->term][$courseinfo->srs] =
                    $adodb->GetRow($sql, $params);
        }

        return $retval;
    }

    /**
     * Ensures that a connection to the registrar is opened.
     *
     * @throws registrar_query_exception    If cannot connect to registrar.
     *
     * @param boolean $debug    Optional.
     * @return ADOConnection
     */
    protected function open_regconnection($debug = false) {
        if (empty($this->adodb)) {            
            $this->adodb = registrar_query::open_registrar_connection();
        }
        $debug ? $this->adodb->debug = true : $this->adodb->debug = false;
        return $this->adodb;
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
        global $CFG;

        // Get all related fields to build key to update records.
        $courseinfos = ucla_get_course_info($courseid);
        if (empty($courseinfos)) {
            // Not a registrar course.
            return self::FAILED;
        }

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
        

        $adodb = $this->open_regconnection();

        // Not all courseinfo entries might bee updated. Need to keep track of
        // partial updates so that we can return the right return codes.
        $hasaffectedrows = false;
        $noupdateoccurred = false;
        foreach ($courseinfos as $courseinfo) {
            // First get any existing records.
            $sql = "SELECT  *
                    FROM    $this->syllabustable
                    WHERE   term_cd=? AND
                            subj_area_cd=? AND
                            crs_catlg_no=? AND
                            sect_no=?";
            // Order matters and must match ? placeholders.
            $whereparams = array($courseinfo->term, $courseinfo->subj_area, 
                    $courseinfo->crsidx, $courseinfo->classidx);
            $existing = $adodb->GetRow($sql, $whereparams);

            $result = null;
            if ($existing === false) {
                // Could not do query.
                return self::FAILED;
            } else if (empty($existing)) {
                // Record does not exist, so insert it.
                $insertparams = array_merge($whereparams, $typevals);
                $placeholders = implode(',', explode(' ', trim(str_repeat('? ',
                        count($insertparams)))));


                $sql = "INSERT INTO $this->syllabustable
                        (term_cd, subj_area_cd, crs_catlg_no, sect_no, " .
                         implode(',', $typecols) . ")
                        VALUES
                        ($placeholders)";
                $result = $adodb->Execute($sql, $insertparams);
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
                    $noupdateoccurred = true;
                    continue;
                }

                $updateparams = array_merge($typevals, $whereparams);

                $sql = "UPDATE $this->syllabustable
                        SET ";

                $firstentry = true;
                foreach ($typecols as $typecol) {
                    $firstentry ? $firstentry = false : $sql .= ',';
                    $sql .= $typecol . ' = ?';
                }

                $sql .= "WHERE  term_cd=? AND
                                subj_area_cd=? AND
                                crs_catlg_no=? AND
                                sect_no=?";
                $result = $adodb->Execute($sql, $updateparams);
            }

            if ($result === false) {
                // Some problem happened.
                return self::FAILED;
            }

            if ($adodb->Affected_Rows()) {
                $hasaffectedrows = true;
            } else {
                // No changed, so must have been an update with no changes.
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
