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
 * Past Course Quiz notice file.
 *
 * Responds to past course quiz notice form. Handles setting of user preferences and
 * redirecting.
 *
 * @package     local_ucla
 * @copyright   2016 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/local/ucla/past_course_visible_quiz_notice_form.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');

// Use the ID of the course to retrieve course records.
$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
require_course_login($course);

$successmessage = null;
$noticeform = new past_course_visible_quiz_notice_form();
$data = $noticeform->get_data();
if (!empty($data) && confirm_sesskey()) {
    if (isset($data->yesbutton)) {
        // Run a SQL query that hides all visible quizzes.
        $quizmoduleid = (int) $DB->get_field('modules', 'id', array('name' => 'quiz'));
        $DB->set_field('course_modules', 'visible', 0,
            array(
                'course' => $courseid,
                'module' => $quizmoduleid
            )
        );
        // Increment course.cacherev for courses where we just made something invisible.
        // This will force cache rebuilding on the next request.
        increment_revision_number('course', 'cacherev', "id = $courseid");
        core_plugin_manager::reset_caches();
        $successmessage = get_string('notice_quizhidden_yes_redirect', 'local_ucla');
    } else if (isset($data->nobutton)) {
        // Set user preference value ucla_quiz_notice_noprompt_<courseid>
        // so that the reminder never shows again.
        set_user_preference('ucla_quiz_notice_noprompt_' . $courseid, UCLA_PAST_QUIZ_NOTICE_NO_REMINDERS);
        $successmessage = get_string('notice_quizhidden_no_redirect', 'local_ucla');
    } else if (isset($data->laterbutton)) {
        // Set user preference value ucla_quiz_notice_noprompt_<courseid> to
        // now + 24 hours.
        set_user_preference('ucla_quiz_notice_noprompt_' . $courseid, time() + 86400);
        $successmessage = get_string('notice_quizhidden_later_redirect', 'local_ucla');
    }

    // Redirect no/later responses to course page (make sure to redirect to
    // landing page or user wouldn't get success message).
    $section = 0;
    $format_options = course_get_format($course->id)->get_format_options();
    if (isset($format_options['landing_page'])) {
        $landing_page = $format_options['landing_page'];
    }
    if (!empty($landing_page)) {
        $section = $landing_page;
    }
    flash_redirect(new moodle_url('/course/view.php',
        array('id' => $courseid, 'section' => $section)), $successmessage);
}
