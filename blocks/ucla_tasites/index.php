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
require_once($CFG->dirroot . '/local/ucla/lib.php');

// Setup parameters.
$courseid = required_param('courseid', PARAM_INT);
$formaction = optional_param('action', null, PARAM_ALPHA);
$course = get_course($courseid);

// Check access.
require_login($courseid);
block_ucla_tasites::check_access($courseid);
if (block_ucla_tasites::is_tasite($courseid)) {
    throw new block_ucla_tasites_exception('erristasite');
}

// Setup page.
$PAGE->set_url(new moodle_url(
        '/blocks/ucla_tasites/index.php',
        array('courseid' => $courseid)
    ));
$PAGE->set_course($course);
$PAGE->set_title(get_string('pluginname', 'block_ucla_tasites'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-' . $course->format);

// Setup form.
$formdata = array(
    'action' => $formaction,
    'courseid' => $courseid
);
$tasitesform = new tasites_form(null, $formdata, 'post', '', array('class' => 'tasites_form'));

$pagebody = '';
if ($formaction == 'create') {
    // User wants to create TA site.
    if (($params = $tasitesform->get_data()) && confirm_sesskey()) {

        // User submitted form, so process it.
        $mapping = block_ucla_tasites::get_tasection_mapping($courseid);

        // What type of TA site does user want?
        $typeinfo = array();
        if (isset($params->bysection)) {
            // What section is user building?
            if ($params->bysection == 'all') {
                $typeinfo['bysection'] = $mapping['bysection'];
            } else {
                $typeinfo['bysection'] = array($mapping['bysection'][$params->bysection]);
            }
        }

        $newtasite = block_ucla_tasites::create_tasite($course, $typeinfo);

        // Save messages in flash and redirect user.
        $redirect = new moodle_url('/blocks/ucla_tasites/index.php',
                array('courseid' => $courseid));

        //flash_redirect($redirect, $messages);
    } else {
        // Display form to process.
        ob_start();
        $tasitesform->display();
        $pagebody = ob_get_contents();
        ob_end_clean();
    }

} else if ($formaction == 'toggle') {
    // Show or hide given TA site.
    $tasiteid = required_param('tasite', PARAM_INT);
    $visiblity = block_ucla_tasites::toggle_visiblity($tasiteid);
    $tasite = get_course($tasiteid);

    $redirect = $url = new moodle_url('/blocks/ucla_tasites/index.php',
            array('courseid' => $courseid));

    if ($visiblity) {
        $message = get_string('sucshowsite', 'block_ucla_tasites', $tasite->shortname);
    } else {
        $message = get_string('suchidsite', 'block_ucla_tasites', $tasite->shortname);
    }

    flash_redirect($redirect, $message);

} else {
    ob_start();
    // Display any messages, if any.
    flash_display();

    // Show existing TA sites.
    $tasites = block_ucla_tasites::get_tasites($courseid);
    $output = $PAGE->get_renderer('block_ucla_tasites');
    echo $output->render_tasites($tasites);

    $url = new moodle_url('/blocks/ucla_tasites/index.php', 
            array('courseid' => $courseid,'action' => 'create'));
    echo $OUTPUT->single_button($url, get_string('create', 'block_ucla_tasites'), 'get');

    $pagebody = ob_get_contents();
    ob_end_clean();
}

// Display page.
echo $OUTPUT->header();
echo $OUTPUT->heading($PAGE->title);
echo $pagebody;
echo $OUTPUT->footer();
