<?php
// This file is part of the UCLA Media block for Moodle - http://moodle.org/
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
 * Upgrades database for Bruincast.
 *
 * @package    block_ucla_media
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_ucla_media\task;
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/ucladatasourcesync/lib.php');

/**
 * Class file.
 *
 * @package    block_ucla_media
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_bcast extends \core\task\scheduled_task {

    /**
     * Checks for crosslist issues and emails $bruincast_errornotify_email to fix.
     *
     * @param array $data   Should be processed data that was validated and matched
     */
    private function check_crosslists(&$data) {
        $errornotifyemail = get_config('block_ucla_bruincast', 'errornotify_email');
        $quietmode = get_config('block_ucla_bruincast', 'quiet_mode');

        $problemcourses = array();

        // Find crosslisted courses.
        foreach ($data as $entries) {
            foreach ($entries as $d) {
                // Get the courseid for a particular TERM-SRS.
                if (isset($d->courseid)) {
                    $courseid = $d->courseid;
                } else {
                    continue;   // Course is not on the system.
                }

                // Find out if it's crosslisted.
                $courses = ucla_map_courseid_to_termsrses($courseid);

                // Enforce:
                // If for a crosslisted course, any of the bruincast urls are restricted,
                // then all of the courses need to have access to the bruincast.
                if (count($courses) > 1) {
                    if (strtolower($d->restricted) == 'restricted') {
                        foreach ($courses as $c) {
                            if (empty($data[$c->term.'-'.$c->srs])) {
                                $msg = "Restricted BruinCast URL is not "
                                        . "associated with crosslisted coures:\n"
                                        . "url: " . $d->bruincast_url . "\n"
                                        . "term: " . $d->term . "\n"
                                        . "srs: " . $d->srs . "\n"
                                        . "affected course srs: " . $c->srs . "\n";

                                $problemcourses[] = $msg;
                            }
                        }
                    }
                }
            }
        }

        $mailbody = implode("\n", $problemcourses);

        // Send problem course details if we have any.
        if (empty($errornotifyemail) || $quietmode) {
            echo $mailbody;
        } else if (trim($mailbody) != '') {
            ucla_send_mail($errornotifyemail,
                    'BruinCast data issues (' . date('r') . ')', $mailbody);
        }
    }

    /**
     * Defines how to validate the fields.
     *
     * @return array
     */
    private function define_data_source() {
        $retval = array();

        $retval[] = array('name' => 'term',
            'type' => 'term',
            'min_size' => '3',
            'max_size' => '3');
        $retval[] = array('name' => 'srs',
            'type' => 'srs',
            'min_size' => '7',
            'max_size' => '9');
        $retval[] = array('name' => 'class',
            'type' => 'string',
            'min_size' => '0',
            'max_size' => '255');
        $retval[] = array('name' => 'restricted',
            'type' => 'string',
            'min_size' => '0',
            'max_size' => '12');
        $retval[] = array('name' => 'bruincast_url',
            'type' => 'url',
            'min_size' => '1',
            'max_size' => '255');
        return $retval;
    }

   /**
    * Update Bruincast database.
    *
    * @return boolean
    */
    public function execute() {
        global $CFG, $DB;

        // Check to see if config variables are initialized.
        $sourceurl = get_config('block_ucla_bruincast', 'source_url');
        if (empty($sourceurl)) {
            log_ucla_data('bruincast', 'read', 'Initializing cfg variables',
                get_string('errbcmsglocation', 'tool_ucladatasourcesync') );
                return;
        }

        // Begin database update.
        echo get_string('bcstartnoti', 'tool_ucladatasourcesync') . "\n";

        $csvdata = get_csv_data($sourceurl);
        if (empty($csvdata)) {
            return false;
        }
        $fields = $this->define_data_source();

        // We know for the bruincast DATA that the first two rows are a
        // timestamp and the column titles, so get rid of them.
        unset($csvdata[0]);
        unset($csvdata[1]);

        $cleandata = array();

        // This is expected bruincast mapping.
        $keymap = array('term', 'srs', 'class', 'restricted', 'bruincast_url');

        // Create an array of table record objects to insert.
        foreach ($csvdata as $datanum => $d) {
            $obj = new \stdClass();

            if (count($d) != count($fields)) {
                echo count($d) . ' vs ' . count($fields); exit;
                echo get_string('errbcinvalidrowlen', 'tool_ucladatasourcesync') . "\n";
                continue;
            }
            $invalidfields = array();
            foreach ($fields as $fieldnum => $fielddef) {
                // Validate/clean data.
                $data = validate_field($fielddef['type'], $d[$fieldnum],
                        $fielddef['min_size'], $fielddef['max_size']);
                if ($data === false) {
                    $invalidfields[] = $fielddef['name'];
                }
            }

            // Give warning about errors.
            if (!empty($invalidfields)) {
                $error = new \stdClass();
                $error->fields = implode(', ', $invalidfields);
                $error->line_num = $datanum;
                $error->data = $d;
                print($d);
                echo(get_string('warninvalidfields', 'tool_ucladatasourcesync',
                        $error) . "\n");
                continue;
            }

            foreach ($keymap as $k => $v) {
                if ($k == 2) {   // Skip 'class' field.
                    continue;
                }
                $obj->$v = $d[$k];
            }

            // Index by term-srs, so that check_crosslists can see if crosslists exist.
            // There can be multiple entries for a given term/srs.
            $cleandata[$obj->term.'-'.$obj->srs][] = $obj;
        }

        // Do not process bruincast data if there are no valid entries.
        if (empty($cleandata)) {
            die(get_string('bcnoentries', 'tool_ucladatasourcesync'). "\n");
        }

        // Drop table if we are processing new entries.
        $DB->delete_records('ucla_bruincast');

        // Insert records.
        try {
            $insertcount = 0;
            foreach ($cleandata as &$termsrsentries) {
                foreach ($termsrsentries as &$cd) {
                    $courseid = match_course($cd->term, $cd->srs);

                    if (!empty($courseid)) {
                        $cd->courseid = $courseid;
                    }

                    $DB->insert_record('ucla_bruincast', $cd);
                    ++$insertcount;
                }
            }

            // Give total inserts.
            echo get_string('bcsuccessnoti', 'tool_ucladatasourcesync', $insertcount) . "\n";

            // Find errors in the crosslisted courses and notify.
            $this->check_crosslists($cleandata);
        } catch (dml_exception $e) {
            // Report a DB insert error.
            echo "\n" . get_string('errbcinsert', 'tool_ucladatasourcesync') . "\n";
        }

        return true;
    }

   /**
    * Returns task name.
    *
    * @return string
    */
    public function get_name() {
        return get_string('taskupdatebcast', 'block_ucla_media');
    }

}