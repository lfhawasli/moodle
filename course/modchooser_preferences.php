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
 * Modchooser preferences page.
 *
 * @package     core_course
 * @copyright   2016 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(__FILE__)) . '/config.php');
require_once('classes/modchooser_preferences_form.php');

$returnto = optional_param('returnto', 0, PARAM_ALPHANUM); // Course to return to. 0 means return to user preferences.

$returnurl = new moodle_url($CFG->wwwroot . '/user/preferences.php');
if ($returnto !== 0) {
    $returnurl = new moodle_url($CFG->wwwroot . '/course/view.php', array('id' => $returnto));
}

$url = new moodle_url('/course/modchooser_preferences.php');

require_login();
$PAGE->set_context(context_user::instance($USER->id));
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');

$args = array(
    'returnto' => $returnto
);
$mform = new course_modchooser_preferences_form(null, $args);
$mform->set_data(array('modchoosersetting' => get_user_preferences('modchoosersetting')));

if (!$mform->is_cancelled() && $data = $mform->get_data()) {
    $setting = $data->modchoosersetting;
    set_user_preference('modchoosersetting', $setting);
    if ($data->returnto != 0) {
        $returnurl = new moodle_url($CFG->wwwroot . '/course/view.php', array('id' => $data->returnto));
    } else {
        $returnurl = new moodle_url($CFG->wwwroot . '/user/preferences.php');
    }
    redirect($returnurl, get_string('modchooserpreferencesupdate'), null, \core\output\notification::NOTIFY_SUCCESS);
}

if ($mform->is_cancelled()) {
    redirect($returnurl);
}

$strpreferences = get_string('preferences');
$strmodchooser = get_string('modchooser');

$title = "$strmodchooser: $strpreferences";
$PAGE->set_title($title);
$PAGE->set_heading(fullname($USER));

echo $OUTPUT->header();
echo $OUTPUT->heading($title, 2);

$mform->display();

echo $OUTPUT->footer();
