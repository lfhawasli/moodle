<?php
// This file is part of UCLA copyright status block for Moodle - http://moodle.org/
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
 * Responds to copyright alert form. Handles setting of user preferences and
 * redirecting.
 *
 * @package    block_ucla_copyrightstatus
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_copyright_status/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_copyright_status/alert_form.php');

$id = required_param('id', PARAM_INT);   // Course id.
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);

require_course_login($course);

if (!has_capability('moodle/course:manageactivities', $coursecontext)) {
    print_error('permission_not_allow', 'block_ucla_copyright_status');
}

$alertform = new copyright_alert_form();
$data = $alertform->get_data();

if (!empty($data) && confirm_sesskey()) {
    if (isset($data->yesbutton)) {
        // Yes: redirect user to manage copyright status with editing turned on.
        $params = array('courseid' => $id);

        // If user is not currently in editing mode, turn it on.
        if (!$USER->editing) {
            $params['edit'] = 1;
            $params['sesskey'] = sesskey();
        }

        redirect(new moodle_url('/blocks/ucla_copyright_status/view.php', $params));
    } else if (isset($data->nobutton)) {
        // No: set user preference ucla_copyright_status_noprompt_<courseid> to 0.
        set_user_preference('ucla_copyright_status_noprompt_' . $id, 0);
        $successmsg = get_string('alert_no_redirect', 'block_ucla_copyright_status');
    } else if (isset($data->laterbutton)) {
        // Later: set user preference value ucla_copyright_status_noprompt_<courseid> to...
        // ...now + 24 hours.
        set_user_preference('ucla_copyright_status_noprompt_' . $id, time() + 86400);
        $successmsg = get_string('alert_later_redirect', 'block_ucla_copyright_status');
    }

    // Redirect no/later responses to course page (make sure to redirect to...
    // ...landing page or user wouldn't get success message).
    $section = 0;
    $formatoptions = course_get_format($course->id)->get_format_options();
    if (isset($formatoptions['landing_page'])) {
        $landingpage = $formatoptions['landing_page'];
    }
    if (!empty($landingpage)) {
        $section = $landingpage;
    }
    flash_redirect(new moodle_url('/course/view.php',
            array('id' => $id, 'section' => $section)), $successmsg);
}
