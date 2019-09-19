<?php
// This file is part of the UCLA Library Reserves block for Moodle - http://moodle.org/
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
 * Upgrades database for library reserves.
 *
 * @package    block_ucla_library_reserves
 * @author     Anant Mahajan
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_ucla_library_reserves\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/ucladatasourcesync/lib.php');

/**
 * Class file.
 *
 * @package    block_ucla_library_reserves
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update extends \core\task\scheduled_task {

    /**
     * Sets up array to be used to validate library reserve data source.
     *
     * Expects library reserves data to be in the following format:
     * Course Number: VARCHAR2(10)
     * Course Name: VARCHAR2(40)
     * Department Code: VARCHAR2(10)
     * Department Name: VARCHAR2(40)
     * Instructor Last Name: VARCHAR2(50)
     * Instructor First Name: VARCHAR2(40)
     * Reserves List Title: VARCHAR2(40)
     * List Effective Date: YYYY-MM-DD
     * List Ending Date: YYYY-MM-DD
     * URL: VARCHAR2
     * SRS Number: VARCHAR2(9)
     * Quarter: CHAR(3)
     *
     * Indices in datasource:
     *      'name',
     *      'type',
     *      'min_size',
     *      'max_size'
     *
     * @return mixed
     */
    private function define_data_source() {
        $retval = array();

        $retval[] = array('name' => 'course_number',
                            'type' => 'coursenum',
                            'min_size' => '0',
                            'max_size' => '10');
        $retval[] = array('name' => 'course_name',
                            'type' => 'string',
                            'min_size' => '0',
                            'max_size' => '40');
        $retval[] = array('name' => 'department_code',
                            'type' => 'string',
                            'min_size' => '0',
                            'max_size' => '10');
        $retval[] = array('name' => 'department_name',
                            'type' => 'string',
                            'min_size' => '0',
                            'max_size' => '40');
        $retval[] = array('name' => 'url',
                            'type' => 'url',
                            'min_size' => '1',
                            'max_size' => '400');
        $retval[] = array('name' => 'srs',
                            'type' => 'srs',
                            'min_size' => '7',  // In case leading zeroes are removed.
                            'max_size' => '9');
        $retval[] = array('name' => 'quarter',
                            'type' => 'term',
                            'min_size' => '3',
                            'max_size' => '3');

        return $retval;
    }

    /**
     * Updates library reserves.
     */
    public function execute() {
        global $DB;

        // Begin database update.
        echo get_string('lrstartnoti', 'tool_ucladatasourcesync') . "\n";

        $parseddata = $this->parse_datasource();
        if (empty($parseddata)) {
            echo get_string('lrnoentries', 'tool_ucladatasourcesync') . "\n";
            return false;
        }

        // Purge the cached hostcourse urls.
        \cache_helper::purge_by_definition('block_ucla_library_reserves', 'hostcourseurl');

        // Wrap everything in a transaction, because we don't want to have an empty.
        // Table while data is being updated.
        $numinserted = 0;
        $transaction = $DB->start_delegated_transaction();
        try {
            // Drop table and refill with data.
            $DB->delete_records('ucla_library_reserves');

            foreach ($parseddata as $reserveentry) {
                // Through each entry and try to match it to a course.

                // Catnum might have x, indicating a secnum.
                $result = explode('x', $reserveentry['course_number']);
                $sectionnum = null;
                $catnum = $result[0];
                if (isset($result[1])) {
                    $sectionnum = $result[1];
                }
                $courseid = match_course($reserveentry['quarter'],
                        $reserveentry['srs'], $reserveentry['department_code'],
                        $catnum, $sectionnum);
                if (!empty($courseid)) {
                    $reserveentry['courseid'] = $courseid;
                }

                if ($DB->insert_record('ucla_library_reserves', $reserveentry)) {
                    ++$numinserted;
                }
            }

            if ($numinserted == 0) {
                log_ucla_data('library reserves', 'write', 'Inserting library reserve data',
                        get_string('errbcinsert', 'tool_ucladatasourcesync') );

                throw new \moodle_exception('errbcinsert', 'tool_ucladatasourcesync');
            }

            // Assuming the both inserts work, we get to the following line.
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
        }

        log_ucla_data('library reserves', 'update',
                get_string('lrsuccessnoti', 'tool_ucladatasourcesync', $numinserted) );

        echo  get_string('lrsuccessnoti', 'tool_ucladatasourcesync',
                $numinserted) . "\n";
    }

    /**
     * Gets data via web service and returns a single array.
     */
    private function get_datasource() {
        $retval = array();

        // Check to see that config variable is initialized.
        $datasourceurl = get_config('block_ucla_library_reserves', 'source_url');
        if (empty($datasourceurl)) {
            log_ucla_data('library reserves', 'read', 'Initializing cfg variables',
                    get_string('errlrmsglocation', 'tool_ucladatasourcesync'));
            return;
        }

        $terms = get_active_terms();
        // Iterating through all active terms and retrieving data for them.
        foreach ($terms as $term) {
            // Get all of the departments.
            $departments = $this->read_xml_data($datasourceurl .
                    '/departments/during/' . $term);
            if (empty($departments)) {
                // Nothing for given term.
                continue;
            }

            $departments = $departments['department'];
            foreach ($departments as $department) {
                // Get all of the courses in each department.
                $courses = $this->read_xml_data($datasourceurl.'/courses/dept/' .
                        $department['departmentID'] . '/term/' . $term);
                if (empty($courses)) {
                    continue;
                }
                $courses = $courses['course'];

                // Handle when there is only one course in this department.
                if (key($courses) === 'courseNumber' || key($courses) === 'courseName') {
                    // Some entries do not have course number.
                    $temp['0'] = $courses;
                    $courses = $temp;
                }

                foreach ($courses as $course) {
                    $entry = array();
                    $entry['course_number'] = isset($course['courseNumber']) ? $course['courseNumber'] : '';
                    $entry['course_name'] = $course['courseName'];
                    $entry['department_code'] = $department['departmentCode'];
                    $entry['department_name'] = $department['departmentName'];
                    $entry['url'] = $course['url'];
                    $entry['srs'] = $course['srsNumber'];
                    $entry['quarter'] = $department['quarter'];
                    $retval[] = $entry;
                }
            }
        }
        return $retval;
    }

    /**
     * Returns task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskupdate', 'block_ucla_library_reserves');
    }

    /**
     * Parses the data at the given source and outputs clean, usable data.
     * @return array    False on error.
     */
    private function parse_datasource() {
        $parseddata = array();

        // Get fields that should be in data source.
        $fields = $this->define_data_source();

        // Flatten web service data into single array.
        $entries = $this->get_datasource();
        if ($entries === false) {
            echo 'returning false';
            log_ucla_data('library reserves', 'read', 'Reading data source url',
                    get_string('errlrfileopen', 'tool_ucladatasourcesync'));
            return false;
        }

        $invalidfieldserrors = array();
        $numentries = 0;
        foreach ($entries as $entry) {
            // Bind incoming data field to local database table.
            // Don't fail if fields are invalid, just note it and continue, because
            // data is very messy, but we will try to work with it when trying to
            // match a library reserve entry with a course.
            $invalidfields = array();
            foreach ($fields as $fieldnum => $fielddef) {
                // Validate/clean data.
                $data = validate_field($fielddef['type'],
                        $entry[$fielddef['name']], $fielddef['min_size'],
                        $fielddef['max_size']);
                if ($data === false) {
                    $invalidfields[] = $fielddef['name'];
                }

                $parseddata[$numentries][$fielddef['name']] = $data;
                ++$fieldnum;
            }

            // Give warning about errors.
            if (!empty($invalidfields)) {
                $error = new \stdClass();
                $error->fields = implode(', ', $invalidfields);
                // @codingStandardsIgnoreLine
                $error->data = print_r($entry, true);

                // Compile a list of parsing errors and send all errors in one email.
                $invalidfieldserrors[] = get_string('warninvalidfields',
                        'tool_ucladatasourcesync', $error) . "\n";

                echo(get_string('warninvalidfields', 'tool_ucladatasourcesync',
                        $error) . "\n");
            }

            ++$numentries;
        }

        if (!empty($invalidfieldserrors)) {
            // Email ccle support about invalid field errors.
            log_ucla_data('library reserves', 'read', 'library reserve invalid',
                    implode("\r\n", $invalidfieldserrors));
        }

        return $parseddata;
    }

    /**
     * Converts data url from web service from XML to PHP array.
     *
     * @param object $url
     * @return boolean|array url in PHP array form.
     */
    private function read_xml_data($url) {
        $xml = simplexml_load_file($url);
        if ($xml === false) {
            log_ucla_data('library reserves', 'read', 'Reading data source url',
                   get_string('errlrfileopen', 'tool_ucladatasourcesync'));
            return false;
        }
        $json = json_encode($xml);
        $jarray = json_decode($json, true);
        return $jarray;
    }

}
