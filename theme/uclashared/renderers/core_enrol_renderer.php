<?php
// This file is part of the UCLA theme plugin for Moodle - http://moodle.org/
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
 * Override Moodle's core enrol renderer.
 *
 * @package    theme_uclashared
 * @copyright  UC Regents 2014
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/enrol/renderer.php');

/**
 * Overriding the core enrol render (/enrol/renderer.php).
 *
 * @copyright  UC Regents 2014
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_uclashared_core_enrol_renderer extends core_enrol_renderer {

    /**
     * Prevent users from editing groups.
     *
     * @param int $userid
     * @param array $groups
     * @param array $allgroups
     * @param bool $canmanagegroups
     * @param moodle_url $pageurl
     * @return string
     */
    public function user_groups_and_actions($userid, $groups, $allgroups, $canmanagegroups, $pageurl) {
        // Easiest solution: prevent editing of groups from this UI.
        return parent::user_groups_and_actions($userid, $groups, $allgroups,
                        false, $pageurl);
    }

    /**
     * Renders a course enrolment table
     *
     * @param course_enrolment_table $table
     * @param moodleform $mform Form that contains filter controls
     * @return string
     */
    public function render_course_enrolment_users_table(course_enrolment_users_table $table,
            moodleform $mform) {

        $table->initialise_javascript();

        $buttons = $table->get_manual_enrol_buttons();
        $buttonhtml = '';
        if (count($buttons) > 0) {
            $buttonhtml .= html_writer::start_tag('div', array('class' => 'enrol_user_buttons'));
            foreach ($buttons as $button) {
                $buttonhtml .= $this->render($button);
            }
            $buttonhtml .= html_writer::end_tag('div');
        }

        $content = '';
        if (!empty($buttonhtml)) {
            $content .= $buttonhtml;
        }
        $content .= $mform->render();

        $content .= $this->output->render($table->get_paging_bar());

        $content .= html_writer::table($table);

        $content .= $this->output->render($table->get_paging_bar());
        if (!empty($buttonhtml)) {
            $content .= $buttonhtml;
        }
        return $content;
    }
}
