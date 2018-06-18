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
 * Specialised restore for format_ucla (based on format_topics)
 *
 * @package   format_ucla
 * @category  backup
 * @copyright 2018 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/lib.php');

/**
 * Specialised restore for format_ucla
 *
 * Processes 'numsections' from the old backup files and hides sections that used to be "orphaned"
 */
class restore_format_ucla_plugin extends restore_format_topics_plugin {

    /**
     * Executed after course restore is complete
     *
     * This method is only executed if course configuration was overridden
     */
    public function after_restore_course() {
        global $DB;

        if (!$this->need_restore_numsections()) {
            // Backup file was made in Moodle 3.3 or later, we don't need to process 'numsections'.
            return;
        }

        $data = $this->connectionpoint->get_data();
        $backupinfo = $this->step->get_task()->get_info();
        if ($backupinfo->original_course_format !== 'ucla' || !isset($data['tags']['numsections'])) {
            // Backup from another course format or backup file does not even have 'numsections'.
            return;
        }

        $numsections = (int)$data['tags']['numsections'];
        $courseid = $this->step->get_task()->get_courseid();
        foreach ($backupinfo->sections as $key => $section) {
            // For each section from the backup file check if it was restored and if was "orphaned" in the original
            // course and mark it as hidden. This will leave all activities in it visible and available just as it was
            // in the original course.
            // Exception is when we restore with merging and the course already had a section with this section number,
            // in this case we don't modify the visibility.
            if ($this->step->get_task()->get_setting_value($key . '_included')) {
                $sectionnum = (int)$section->title;
                if ($sectionnum > $numsections && $sectionnum > $this->originalnumsections) {
                    $DB->execute("UPDATE {course_sections} SET visible = 0 WHERE course = ? AND section = ?",
                        [$courseid, $sectionnum]);
                }
            }
        }

        // Create missing sections (as in upgrade script).
        $existingsections = $DB->get_fieldset_sql('SELECT section from {course_sections} WHERE course = ?', [$courseid]);
        $newsections = array_diff(range(0, $numsections), $existingsections);
        foreach ($newsections as $sectionnum) {
            course_create_section($courseid, $sectionnum, true);
        }
    }
}
