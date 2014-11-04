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
 * Allows a user to request a course be created for them.
 *
 * @copyright 1999 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package course
 */

require_once(dirname(__FILE__) . '/../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/request_form.php');

// Where we came from. Used in a number of redirects.
$url = new moodle_url('/course/request.php');
// START UCLA MOD CCLE-2389 - redirecting to my-sites instead
//$return = optional_param('return', null, PARAM_ALPHANUMEXT);
//if ($return === 'management') {
//    $url->param('return', $return);
//    $returnurl = new moodle_url('/course/management.php', array('categoryid' => $CFG->defaultrequestcategory));
//} else {
//    $returnurl = new moodle_url('/course/index.php');
//}
$returnurl = $CFG->wwwroot . '/my/';
// END UCLA MOD CCLE-2389

$PAGE->set_url($url);

// Check permissions.
require_login();
if (isguestuser()) {
    print_error('guestsarenotallowed', '', $returnurl);
}
if (empty($CFG->enablecourserequests)) {
    print_error('courserequestdisabled', '', $returnurl);
}
$context = context_system::instance();
$PAGE->set_context($context);
require_capability('moodle/course:request', $context);

// Set up the form.
$data = course_request::prepare();
$requestform = new course_request_form($url, compact('editoroptions'));
$requestform->set_data($data);

// START UCLAMOD CCLE-2389 - override string
//$strtitle = get_string('courserequest');
$strtitle = get_string('courserequest', 'tool_uclasiteindicator');
// END UCLAMOD CCLE-2389 
$PAGE->set_title($strtitle);
$PAGE->set_heading($strtitle);

// Standard form processing if statement.
if ($requestform->is_cancelled()){
    redirect($returnurl);

} else if ($data = $requestform->get_data()) {
    // START UCLAMOD CCLE-2389 - clean shortname, add request to table, display message
//    $request = course_request::create($data);
//
//    // And redirect back to the course listing.
//    notice(get_string('courserequestsuccess'), $returnurl);
    $data->category = $data->indicator_category;    // So the course_request object gets the right category.
    siteindicator_manager::clean_shortname($data);
    $request = course_request::create($data);
    siteindicator_manager::create_request($data);

    // And redirect back to the course listing.
    notice(get_string('courserequestsuccess', 'tool_uclasiteindicator'), $returnurl);
    // END UCLAMOD CCLE-2389
}

$PAGE->navbar->add($strtitle);
echo $OUTPUT->header();
echo $OUTPUT->heading($strtitle);
// Show the request form.
$requestform->display();
echo $OUTPUT->footer();
