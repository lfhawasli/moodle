<?php
// This file is part of the UCLA Senior Scholar site Invitation Plugin for Moodle - http://moodle.org/
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
 * Viewing senior scholar invitation history script.
 *
 * @package    tool_uclaseniorscholar
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/seniorscholar_invitation_form.php');

$bulkuploaddata = required_param('bulkuploaddata', PARAM_TEXT);

$syscontext = context_system::instance();
$PAGE->set_context($syscontext);
$PAGE->set_pagetype('admin-*');
$PAGE->set_pagelayout('admin');
$PAGE->set_heading(get_string('pluginname', 'tool_uclaseniorscholar'));

// Initialize $PAGE.
$PAGE->set_url('/admin/tool/uclaseniorscholar/seniorscholar_bulkinvites.php');

require_login();
if (!seniorscholar_has_access($USER)) {
    print_error('nopermissions');
}

// Render page.
echo $OUTPUT->header();

// Upload only need courseid (srs, term), default role id, email address, everything else default.
// New invite only.
// Data format: colunm 0: srs/column 1: email.

$bulkuploadarray = unserialize($bulkuploaddata);
if (empty($bulkuploadarray)) {
    echo get_string('nobulkinvite', 'tool_uclaseniorscholar');
} else {
    foreach ($bulkuploadarray as $courseid => $record) {
        $invitationmanager = new seniorscholar_invitation_manager($courseid, true);
        $instance = $invitationmanager->get_invitation_instance($courseid, true);
        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $mform = new seniorscholar_invitation_form(null, array('course' => $course, 'prefilled' => ''),
        'post', '', array('class' => 'mform-invite'));
        $mform->set_data($invitationmanager);
        $data = $mform->get_data_without_submission();
        if ($data) {
            foreach ($record as $k => $v) {
                $data->email = $v;
                $invitationmanager->send_invitations($data);
            }
        }
    }
    echo get_string('invitationsuccess', 'tool_uclaseniorscholar');
}
$return = new moodle_url('/admin/tool/uclaseniorscholar/index.php');
echo $OUTPUT->continue_button($return);

echo $OUTPUT->footer();