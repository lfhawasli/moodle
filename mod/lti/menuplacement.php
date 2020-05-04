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
 * This page allows instructors to configure course level tool providers.
 *
 * @package    mod_lti
 * @copyright  2020 The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     David Shepard
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/lti/lib.php');
require_once($CFG->dirroot.'/mod/lti/locallib.php');
require_once($CFG->dirroot.'/mod/lti/menuplacement_form.php');

$courseid = required_param('course', PARAM_INT);

require_login($courseid, false);
$url = new moodle_url('/mod/lti/menuplacement.php');
$url->param('course', $courseid);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

$PAGE->set_url($url);
$PAGE->set_heading($course->fullname);
$PAGE->set_title(format_string($course->shortname) . ': ' . get_string('selectcourseapptitle', 'mod_lti'));

require_capability('mod/lti:addcoursetool', context_course::instance($courseid));

$customdata['courseid'] = $courseid;
$customdata['menulinks'] = lti_load_course_menu_links($courseid);
$form = new mod_lti_menuplacement_form($url, $customdata);

$redirect = new moodle_url('/course/view.php', array('id' => $courseid));
if ($form->is_cancelled()) {
    redirect($redirect);
}

if ($fromform = $form->get_data()) {
    lti_set_course_menu_links($courseid, (array) $fromform);
    redirect($redirect, get_string('changessaved', 'mod_lti'), null,
            \core\output\notification::NOTIFY_SUCCESS);
}

$heading = get_string('courseapps', 'mod_lti');
$PAGE->navbar->add($heading, $url);
echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

$courseappsexist = count($customdata['menulinks']);
if ($courseappsexist) {
    echo get_string('courseappselectionmessage', 'mod_lti');
    echo $OUTPUT->box_start('generalbox');
    $form->display();
    echo $OUTPUT->box_end();
} else {
    echo get_string('courseappselectionmissingmessage', 'mod_lti');
}

echo $OUTPUT->footer();