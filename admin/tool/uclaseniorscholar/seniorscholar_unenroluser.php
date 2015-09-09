<?php
// This file is part of the UCLA senior scholar site invitation plugin for Moodle - http://moodle.org/
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

/* UCLA senior scholar: unenroll senior scholar
 *
 * @package     tool
 * @subpackage  uclaseniorscholar
 * @copyright   UC Regents 2015
 */

require('../../../config.php');
require_once("$CFG->dirroot/enrol/locallib.php");
require_once("$CFG->dirroot/enrol/renderer.php");
require_once(dirname(__FILE__) . '/lib.php');

$ueid = required_param('ueid', PARAM_INT); // Enrol id in enrol table.
$courseid  = required_param('courseid', PARAM_INT); // Course id in invitation table.
$userid = required_param('userid', PARAM_INT); // User id in invitation table.
$confirm = optional_param('confirm', false, PARAM_BOOL);

// Get the user enrolment object.
$ue = $DB->get_record('user_enrolments', array('id' => $ueid), '*', MUST_EXIST);
// Get the user for whom the enrolment is.
$user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
// Get the course the enrolment is to.
$ctxsql = ', ' . context_helper::get_preload_record_columns_sql('ctx');
$ctxjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)";
$sql = "SELECT c.* $ctxsql
          FROM {course} c
     LEFT JOIN {enrol} e ON e.courseid = c.id
               $ctxjoin
         WHERE e.id = :enrolid";
$params = array('enrolid' => $ue->enrolid, 'contextlevel' => CONTEXT_COURSE);
$course = $DB->get_record_sql($sql, $params, MUST_EXIST);
context_helper::preload_from_record($course);

require_login();
if (!seniorscholar_has_access($USER)) {
    print_error('nopermissions');
}

$manager = new course_enrolment_manager($PAGE, $course, 'invitation');
$table = new course_enrolment_users_table($manager, $PAGE);

// The URL of the enrolled users page for the course.
$returnurl = new moodle_url('/admin/tool/uclaseniorscholar/seniorscholar_history.php', array('courseid' => $course->id));
// The URL of this page.
$url = new moodle_url('/admin/tool/uclaseniorscholar/seniorscholar_unenroluser.php', $returnurl->params());
$url->param('ueid', $ueid);
$url->param('userid', $userid);

$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

list($instance, $plugin) = $manager->get_user_enrolment_components($ue);

// Senior scholar invites shown as "invitation" in the enrol_invitation table, so the plugin instance is enrol_invitation.
if (!$plugin->allow_unenrol($instance) || $instance->enrol != 'invitation' || !($plugin instanceof enrol_invitation_plugin)) {
    print_error('erroreditenrolment', 'enrol');
}

// If the unenrolment has been confirmed and the sesskey is valid unenrol the user.
if ($confirm && confirm_sesskey() && $manager->unenrol_user($ue)) {
    redirect($returnurl);
}

$yesurl = new moodle_url($PAGE->url, array('confirm'=>1, 'sesskey'=>sesskey()));
$message = get_string('unenroluser', 'tool_uclaseniorscholar', array('user'=>fullname($user, true),
    'course' => format_string($course->fullname)));
$fullname = fullname($user);
$title = get_string('unenrol', 'tool_uclaseniorscholar');

$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($title);
$PAGE->navbar->add($fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($fullname);
echo $OUTPUT->confirm($message, $yesurl, $returnurl);
echo $OUTPUT->footer();