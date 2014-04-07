<?php
// This file is part of the UCLA stats report for Moodle - http://moodle.org/
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
 * Returns number of files over 1 MB, size of file system, and size of database
 *
 * @package    report_uclastats
 * @copyright  2014 UC Regents
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

/**
 * Report class.
 *
 * @package    report_uclastats
 * @copyright  2014 UC Regents
 */
class system_size extends uclastats_base {

    /**
     * Instead of counting results, but return actual count.
     *
     * @param array $results
     * @return string
     */
    public function format_cached_results($results) {
        if (!empty($results)) {
            $result = array_pop($results);
            if (isset($result['file_count'])) {
                return $result['file_count'];
            }
        }
        return 0;
    }

    /**
     * Returns an array of form elements used to run report.
     */
    public function get_parameters() {
        return array('term');
    }

    /**
     * Querying on the mdl_file can take a long time.
     *
     * @return boolean
     */
    public function is_high_load() {
        return true;
    }

    /**
     * Query for number of files over 1 MB
     *
     * @param array $params
     * @param return array
     */
    public function query($params) {
        global $CFG, $DB;

        $retval = array();

        // Get cumulative file size. Note, this command can take over
        $cumulativefilesize = shell_exec("du -s --block-size=1 $CFG->dataroot/filedir/");
        $retval['cumulativefilesize'] = display_size($cumulativefilesize);

        // Get term specific file size. We need to do 2 queries. One to get
        // course related files and then another for course module related
        // files.

        // Get all files related to course modules.
        $sql = "SELECT SUM(f.filesize) as modulefilesize "
                     . $this->from_filtered_courses(true) .
                 "JOIN {course_modules} cm ON (c.id=cm.course)
                  JOIN {context} ctx ON (ctx.instanceid=cm.id AND ctx.contextlevel=:contextlevel)
                  JOIN {files} f ON f.contextid=ctx.id
                 WHERE 1";
        $modulefilesize = $DB->get_field_sql($sql, 
                array('term' => $params['term'], 'contextlevel' => CONTEXT_MODULE));

        // Get all files related to courses.
        $sql = "SELECT SUM(f.filesize) as coursefilesize "
                     . $this->from_filtered_courses(true) .
                 "JOIN {context} ctx ON (ctx.instanceid=c.id AND ctx.contextlevel=:contextlevel)
                  JOIN {files} f ON f.contextid=ctx.id
                 WHERE 1";
        $coursefilesize = $DB->get_field_sql($sql,
                array('term' => $params['term'], 'contextlevel' => CONTEXT_COURSE));

        $retval['termfilesize'] = display_size($modulefilesize + $coursefilesize);

        // Get count of distinct files over 1MB.
        $sql = "SELECT COUNT(DISTINCT contenthash)
                  FROM {files}
                 WHERE filesize > 1048576";
        $retval['file_count'] = $DB->get_field_sql($sql);

        // Get size of database in bytes.
        $sql = "SELECT SUM(data_length + index_length)
                  FROM information_schema.tables
                 WHERE table_schema = 'moodle'";
        $retval['database_size'] = display_size($DB->get_field_sql($sql));

        return array($retval);
    }

}
