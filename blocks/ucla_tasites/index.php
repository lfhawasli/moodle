<?php
// This file is part of the UCLA TA sites block for Moodle - http://moodle.org/
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
 * Index page.
 *
 * @package    block_ucla_tasites
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__) . '/../../config.php');

require_oncE($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_tasites/block_ucla_tasites.php');
require_once($CFG->dirroot . '/blocks/ucla_tasites/tasites_form.php');
require_once($CFG->dirroot . '/blocks/ucla_tasites/form_response.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');

$courseid = required_param('courseid', PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid));

require_login($courseid);
block_ucla_tasites::check_access($courseid);

if (block_ucla_tasites::is_tasite($courseid)) {
    throw new block_ucla_tasites_exception('erristasite');
}


$PAGE->set_url(new moodle_url(
        '/blocks/ucla_tasites/index.php',
        array('courseid' => $courseid)
    ));

$PAGE->set_course($course);
$PAGE->set_title(get_string('pluginname', 'block_ucla_tasites'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-' . $course->format);

// Get all potentional TA users and their according TA sites
// from {role_assignments}.
$tasra = block_ucla_tasites::get_tasite_users($courseid);
if (!empty($tasra)) {
    // Used for user_get_users_by_id.
    $userids = array();

    // Index $tasra by userid.
    $tas = array();
    foreach ($tasra as $tara) {
        $userid = $tara->userid;
        $userids[] = $userid;
        $tas[$userid] = $tara;
    }

    // From user table.
    $users = user_get_users_by_id($userids);

    // From enrol table indexed by customint4.
    $existingtasites = block_ucla_tasites::get_tasites($courseid);

    // Create $tasiteinfo array.
    $tasiteinfo = array();
    foreach ($users as $userid => $user) {
        if (!empty($existingtasites[$userid])) {
            // Associate ta to TA-site.
            $tasite = $existingtasites[$userid];
            $user->tasite = $tasite;

            // These are all for display sake.
            $courseurl = new moodle_url('/course/view.php', array('id' => $tasite->id));
            $user->courseurl = $courseurl->out();

            $user->courseshortname = $tasite->shortname;
        }

        // Some more shortcuts.
        $user->fullname = fullname($user);
        $user->parentcourse = $course;

        $tasiteinfo[$userid] = $user;
    }

    $formdata = array(
        'courseid' => $courseid,
        'tasiteinfo' => $tasiteinfo
    );

    $tasitesform = new tasites_form(null, $formdata, 'post', '', array('class' => 'tasites_form'));
}

// Process any forms, if user confirmed.
if (optional_param('confirm', 0, PARAM_BOOL) && confirm_sesskey()) {
    foreach ($tasiteinfo as $tasite) {
        // What action is user trying to do?
        $actionname = block_ucla_tasites::action_naming($tasite);
        $action = optional_param($actionname, false, PARAM_ALPHA);
        if (empty($action)) {
            debugging('Could not find registered action for '
                . $tasite->username);
            continue;
        }

        $fn = 'block_ucla_tasites_respond_' . $action;

        // Perform action.
        $checkboxname = block_ucla_tasites::checkbox_naming($tasite);
        $checked = optional_param($checkboxname, false, PARAM_BOOL);
        if (!empty($checked) && empty($existingtasites[$tasite->id])) {
            if (!function_exists($fn)) {
                throw new block_ucla_tasites_exception('errbadresponse', $fn);
            }
            $a = $fn($tasite);
            $messages[] = get_string($a->mstr, 'block_ucla_tasites', $a->mstra);
        }
    }

    // Save messages in flash and redirect user.
    $redirect = $url = new moodle_url('/blocks/ucla_tasites/index.php',
            array('courseid' => $courseid));

    // If there are many success messages, then display in list, else just
    // show one message.
    if (!empty($messages)) {
        if (count($messages) > 1) {
            $messages = html_writer::alist($messages);
        } else {
            $messages = array_pop($messages);
        }
        flash_redirect($redirect, $messages);
    }
}

// Display everything else.
echo $OUTPUT->header();
echo $OUTPUT->heading($PAGE->title);
if (empty($tasra)) {
    // User is accessing TA sites page when they cannot set one up.
    $rolefullname = $DB->get_field('role', 'name', array(
            'id' => block_ucla_tasites::get_ta_role_id()
        ));

    throw new block_ucla_tasites_exception('notasites', $rolefullname);
} else if (($params = $tasitesform->get_data()) && confirm_sesskey()) {
    // User submitted form, but first needs to confirm it.

    // Unset submit button value.
    unset($params->submitbutton);

    // Create confirm message, url passed needs to have all form elements. the
    // single_button renderer will make the url param array into hidden form
    // elements.
    $params->sesskey = sesskey();
    $params->confirm = 1;
    $url = new moodle_url('/blocks/ucla_tasites/index.php', (array)$params);
    $button = new single_button($url, get_string('yes'), 'post');

    // Cancel button takes them back to the page the TA site page.
    $return = new moodle_url('/blocks/ucla_tasites/index.php',
            array('courseid' => $courseid));

    echo $OUTPUT->confirm(get_string('tasitecreateconfirm', 'block_ucla_tasites'),
            $button, $return);

} else {
    // Display any messages, if any.
    flash_display();
    $tasitesform->display();
}

echo $OUTPUT->footer();
