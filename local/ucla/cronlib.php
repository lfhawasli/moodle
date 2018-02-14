<?php
// This file is part of the UCLA local_ucla plugin for Moodle - http://moodle.org/
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
 * Shared UCLA-written for cron-synching functions.
 *
 * @package    local_ucla
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot  . '/' . $CFG->admin .
        '/tool/uclacoursecreator/uclacoursecreator.class.php');

/**
 * Updates the ucla_reg_classinfo table.
 *
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ucla_reg_classinfo_cron {

    /**
     * Translates enrollment code to string.
     *
     * @param char $char
     *
     * @return string
     */
    public static function enrolstat_translate($char) {
        $codes = array(
            'X' => 'Cancelled',
            'O' => 'Opened',
            'C' => 'Closed',
            'W' => 'Waitlisted',
            'H' => 'Hold'
        );

        if (!isset($codes[$char])) {
            return 'Unknown Enrollment Code';
        }

        return $codes[$char];
    }

    /**
     * Compares coursetitle and sectiontitle fields and if they are different
     * will update course title if entry is for hostcourse.
     *
     * @param stdClass $old Database object.
     * @param array $new    Note, $new comes in as an array, not object.
     * @return boolean      Returns true if records needs to be updated,
     *                      otherwise returns false.
     */
    public function handle_course_title(stdClass $old, array $new) {
        global $DB;
        // Check if we need to update course titles.
        if ($old->coursetitle == $new['coursetitle'] &&
                $old->sectiontitle == $new['sectiontitle']) {
            return false;
        }

        // Titles are different, but do they belong to the hostcourse?
        $request = $DB->get_record('ucla_request_classes',
                array('term' => $old->term, 'srs' => $old->srs));
        if (!$request->hostcourse) {
            return false;
        }

        // Entry is for host course, so update course title.
        $fullname = uclacoursecreator::make_course_title(
                $new['coursetitle'], $new['sectiontitle']);
        $DB->set_field('course', 'fullname', $fullname,
                array('id' => $request->courseid));

        mtrace(sprintf("Updated course title for %s, %s to %s",
                $old->term, $old->srs, $fullname));

        return true;
    }

    /**
     * Compares old and new entries from Registrar. An update is really needed
     * only if the following fields are different:
     *  - acttype
     *  - coursetitle
     *  - sectiontitle
     *  - enrolstat
     *  - url
     *  - crs_desc (course description)
     *  - crs_summary (class description)
     *
     * @param stdClass $old Database object.
     * @param array $new    Note, $new comes in as an array, not object.
     * @return boolean      Returns true if records needs to be updated,
     *                      otherwise returns false.
     */
    public function needs_updating(stdClass $old, array $new) {
        $checkfields = array('acttype', 'coursetitle', 'sectiontitle',
            'enrolstat', 'url', 'crs_desc', 'crs_summary');

        foreach ($checkfields as $field) {
            if ($old->$field != $new[$field]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Queries given stored procedure for given data.
     *
     * @param string $sp
     * @param array $data
     *
     * @return array
     */
    public function query_registrar($sp, $data) {
        return registrar_query::run_registrar_query($sp, $data);
    }

    /**
     * Get courses from the ucla_request_classes table for a given term. Query
     * the registrar for those courses.
     *
     * From the records that are returned from the registrar then insert or
     * update records in the ucla_reg_classinfo table.
     *
     * Then for the courses that didn't have any data returned to them from
     * the registrar, then mark those courses as cancelled in the
     * ucla_reg_classinfo table.
     *
     * @param array $terms
     * @return boolean
     */
    public function run($terms) {
        global $DB;

        if (empty($terms)) {
            return true;
        }

        // Get courses from our request table.
        list($sqlin, $params) = $DB->get_in_or_equal($terms);
        $where = 'term ' . $sqlin;
        $urcrecords = $DB->get_recordset_select('ucla_request_classes',
            $where, $params);
        if (!$urcrecords->valid()) {
            return true;
        }

        // Updated.
        $uc = 0;
        // No change needed.
        $nc = 0;
        // Inserted.
        $ic = 0;
        // For cancelled courses later.
        $notfoundatregistrar = array();

        // Get the data from the Registrar and process it.
        foreach ($urcrecords as $request) {
            $reginfo = $this->query_registrar('ccle_getclasses',
                    array(
                        'term' => $request->term,
                        'srs' => $request->srs
                    )
                );
            if (!$reginfo) {
                mtrace("No data for {$request->term} {$request->srs}");
                $regclass = $DB->get_record('ucla_reg_classinfo',
                        array('term' => $request->term, 'srs' => $request->srs));
                // Query registrar with reg_classinfo data to see if course still exists.
                $coursesrs = $this->query_registrar('ucla_get_course_srs',
                    array(
                        'term' => $regclass->term,
                        'subjarea' => $regclass->subj_area,
                        'crsidx' => $regclass->crsidx,
                        'secidx' => $regclass->secidx,
                    )
                );
                if (!$coursesrs) {
                    // The course was not found in the registrar, it no longer exists.
                    $notfoundatregistrar[] = array('term' => $request->term, 'srs' => $request->srs);
                } else {
                    // The course does exist, but with a new srs.
                    $outerarray = reset($coursesrs);
                    $newsrs = reset($outerarray);
                    mtrace("New srs {$newsrs} found for {$request->term} {$request->srs}");

                    // Query the registrar again, with new termsrs data, so we can check
                    // if other values need to be updated.
                    $newreginfo = $this->query_registrar('ccle_getclasses',
                        array(
                            'term' => $request->term,
                            'srs' => $newsrs
                        )
                    );
                    // Update record in ucla_reg_classinfo and ucla_request_classes tables.
                    $newclassinfo = reset($newreginfo);
                    try {
                        $transaction = $DB->start_delegated_transaction();
                        $newclassinfo['id'] = $regclass->id;
                        $DB->update_record('ucla_reg_classinfo', $newclassinfo);
                        $this->handle_course_title($regclass, $newclassinfo);
                        $DB->set_field('ucla_request_classes', 'srs', $newsrs, array(
                            'term' => $request->term,
                            'srs' => $request->srs
                        ));

                        $transaction->allow_commit();
                        $uc++;
                    } catch (\Exception $ex) {
                        $transaction->rollback($ex);
                        mtrace("Update to {$request->term} {$request->srs} failed - records rolled back to original state");
                    }
                }
            } else {
                $newclassinfo = reset($reginfo);  // Result is in an array.

                // See if we need to insert/update ucla_reg_classinfo.
                $existingclassinfo = $DB->get_record('ucla_reg_classinfo',
                        array('term' => $request->term, 'srs' => $request->srs));
                if (!empty($existingclassinfo)) {
                    // Exists, so see if we need to update.
                    $newclassinfo['id'] = $existingclassinfo->id;
                    if (self::needs_updating($existingclassinfo, $newclassinfo)) {
                        $DB->update_record('ucla_reg_classinfo', $newclassinfo);
                        $this->handle_course_title($existingclassinfo, $newclassinfo);
                        $uc++;
                    } else {
                        $nc++;
                    }
                } else {
                    $DB->insert_record('ucla_reg_classinfo', $newclassinfo);
                    $ic++;
                }

            }
        }
        $urcrecords->close();

        // Mark courses that the registrar didn't have data for as "cancelled".
        $numnotfound = 0;
        if (!empty($notfoundatregistrar)) {
            foreach ($notfoundatregistrar as $termsrs) {
                // Try to update entry in ucla_reg_classinfo (if exists) and
                // mark it as cancelled.
                $DB->set_field('ucla_reg_classinfo', 'enrolstat', 'X', $termsrs);
                ++$numnotfound;
            }
        }

        mtrace("Updated: $uc . Inserted: $ic . Not found at registrar: "
            . "$numnotfound . No update needed: $nc");

        return true;
    }
}

/**
 * Fills the subject area cron table.
 *
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ucla_reg_subjectarea_cron {

    /**
     * Gets the subject areas for the given terms and ucla_reg_subjectarea table.
     * @param array $terms
     * @return boolean
     */
    public function run($terms) {
        global $DB;

        $ttte = 'ucla_reg_subjectarea';

        if (empty($terms)) {
            debugging('NOTICE: empty $terms for ucla_reg_subjectarea_cron');
            return true;    // Maybe not an error.
        }

        $reg = registrar_query::get_registrar_query('cis_subjectareagetall');
        if (!$reg) {
            mtrace("No registrar module found.");
        }

        $subjareas = array();
        foreach ($terms as $term) {
            try {
                $regdata = $reg->retrieve_registrar_info(array('term' => $term));
            } catch (\Exception $e) {
                // Mostly likely couldn't connect to registrar.
                mtrace($e->getMessage());
                return false;
            }

            if ($regdata) {
                $subjareas = array_merge($subjareas, $regdata);
            }
        }

        if (empty($subjareas)) {
            debugging('ERROR: empty $subjareas for ucla_reg_subjectarea_cron');
            return false;   // Most likely an error.
        }

        $checkers = array();
        foreach ($subjareas as $k => $subjarea) {
            $newrec = new stdClass();

            $t =& $subjareas[$k];
            $t = array_change_key_case($subjarea, CASE_LOWER);
            $t['modified'] = time();

            $satext = $t['subjarea'];
            $checkers[] = $satext;
        }

        list($sqlin, $params) = $DB->get_in_or_equal($checkers);
        $sqlwhere = 'subjarea ' . $sqlin;

        $selected = $DB->get_records_select($ttte, $sqlwhere, $params,
            '', 'TRIM(subjarea), id');

        $newsa = 0;
        $updsa = 0;

        foreach ($subjareas as $sa) {
            $satext = $sa['subjarea'];
            if (empty($selected[$satext])) {
                $DB->insert_record($ttte, $sa);

                $newsa ++;
            } else {
                $sa['id'] = $selected[$satext]->id;
                $DB->update_record($ttte, $sa);
                $updsa ++;
            }

        }

        mtrace("New: $newsa. Updated: $updsa.");

        return true;
    }
}

/**
 * CCLE-3739 - Do not allow "UCLA registrar" enrollment plugin to be hidden.
 *
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ucla_reg_enrolment_plugin_cron {
    /**
     * Finds courses with database enrollment plugin hidden and enables it.
     *
     * @return boolean
     */
    public function run() {
        global $DB;

        // Find courses whose registrar (database) enrolment has been disabled
        // @note 'enrol.status' is not indexed, but it's a boolean value.
        $records = $DB->get_records('enrol', array('enrol' => 'database',
            'status' => ENROL_INSTANCE_DISABLED));

        // Now enable them the Moodle way.
        foreach ($records as $r) {
            self::update_plugin($r->courseid, $r->id);
        }

        return true;
    }

    /**
     * Enables database enrollment plugin.
     *
     * @param int $courseid
     * @param int $instanceid
     */
    static public function update_plugin($courseid, $instanceid) {
        $instances = enrol_get_instances($courseid, false);
        $plugins   = enrol_get_plugins(false);

        // Straight out of enrol/instances.php.
        $instance = $instances[$instanceid];
        $plugin = $plugins[$instance->enrol];
        if ($instance->status != ENROL_INSTANCE_ENABLED) {
            $plugin->update_status($instance, ENROL_INSTANCE_ENABLED);
        }
    }
}
