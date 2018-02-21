<?php
// This file is part of Moodle - http://moodle.org/
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
 * Upgrade scripts for course format ucla (mostly copied from "Topics")
 *
 * @package    format_ucla
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/topics/db/upgradelib.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Find courses with different number of sections from their 'numsections' course format option.
 * 
 * For each such course:
 * - If there are more sections than numsections, handle the "orphaned sections"
 * with the topics upgrade code in format_topics_upgrade_hide_extra_sections().
 * - If there are fewer sections than numsections, create missing sections.
 */
function format_ucla_upgrade_remove_numsections() {
    global $DB;

    $sql1 = "SELECT c.id, max(cs.section) AS sectionsactual
          FROM {course} c
          JOIN {course_sections} cs ON cs.course = c.id
          WHERE c.format = :format1
          GROUP BY c.id";

    $sql2 = "SELECT c.id, n.value AS numsections
          FROM {course} c
          JOIN {course_format_options} n ON n.courseid = c.id AND n.format = :format1 AND n.name = :numsections AND n.sectionid = 0
          WHERE c.format = :format2";

    $params = ['format1' => 'ucla', 'format2' => 'ucla', 'numsections' => 'numsections'];

    $actual = $DB->get_records_sql_menu($sql1, $params);
    $numsections = $DB->get_records_sql_menu($sql2, $params);
    $toomanysections = [];
    $toofewsections = [];

    $defaultnumsections = get_config('moodlecourse', 'numsections');

    foreach ($actual as $courseid => $sectionsactual) {
        if (array_key_exists($courseid, $numsections)) {
            $n = (int)$numsections[$courseid];
        } else {
            $n = $defaultnumsections;
        }
        if ($sectionsactual > $n) {
            $toomanysections[$courseid] = $n;
        } else if ($sectionsactual < $n) {
            $toofewsections[$courseid] = $n;
        }
    }
    unset($actual);
    unset($numsections);

    foreach ($toomanysections as $courseid => $numsections) {
        // We can reuse this function since it has no topics-specific code.
        format_topics_upgrade_hide_extra_sections($courseid, $numsections);
    }

    // Create missing sections.
    foreach ($toofewsections as $courseid => $numsections) {
        // Based on course/lib.php:create_course
        $existingsections = $DB->get_fieldset_sql('SELECT section from {course_sections} WHERE course = ?', [$courseid]);
        $newsections = array_diff(range(0, $numsections), $existingsections);
        foreach ($newsections as $sectionnum) {
            // Defined in course/lib.php
            course_create_section($courseid, $sectionnum, true);
        }
    }

    $DB->delete_records('course_format_options', ['format' => 'ucla', 'sectionid' => 0, 'name' => 'numsections']);
}
