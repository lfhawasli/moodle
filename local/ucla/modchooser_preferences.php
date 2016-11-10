<?php
// This file is part of the UCLA local plugin for Moodle - http://moodle.org/
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
 * @package     local_ucla
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('classes/modchooser_preferences_form.php');

$url = new moodle_url('/local/ucla/modchooser_preferences.php');

require_login();
$PAGE->set_context(context_user::instance($USER->id));
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');

$mform = new local_ucla_modchooser_preferences_form();
$mform->set_data(array('modchoosersetting' => get_user_preferences('modchoosersetting')));

if (!$mform->is_cancelled() && $data = $mform->get_data()) {
    $setting = $data->modchoosersetting;
    set_user_preference('modchoosersetting', $setting);
}

if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot . '/user/preferences.php');
}

$strpreferences = get_string('preferences');
$strmodchooser = get_string('modchooser');

$title = "$strmodchooser: $strpreferences";
$PAGE->set_title($title);
$PAGE->set_heading(fullname($USER));

echo $OUTPUT->header();
echo $OUTPUT->heading("$strmodchooser: $strpreferences", 2);

$mform->display();

echo $OUTPUT->footer();
