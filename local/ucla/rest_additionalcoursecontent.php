<?php
/**
 * Checks if a course has any additional content given a course id.
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/report/uclastats/reports/active_instructor_focused.php');

global $DB, $USER;

try {
    $course = $DB->get_record('course', array('id' => required_param('courseid', PARAM_INT)), '*', MUST_EXIST);
} catch (Exception $e) {
    // Both get_record and required_param will fail with exceptions with an invalid courseid.
}

$activeinstructorfocused = new active_instructor_focused($USER);

$obj = new stdClass();

if ($activeinstructorfocused->has_additional_course_content($course)) {
    // Display warning message that this is a course for which content has been added.
    $deleterestoremsg = html_writer::tag('p', get_string('deletewarning', 'local_ucla'),
                                         array("style" => "font-size: 20px; font-weight: bold; color: red;")) .
                                         get_string('deleterestorewarning', 'local_ucla') . html_writer::empty_tag('br') .
                                         get_string('backuprestore', 'local_ucla');
} else {
    // Display safe message that this is a course for which no content has been added.
    $deleterestoremsg = get_string('deleterestoresafe', 'local_ucla') . html_writer::empty_tag('br') .
            get_string('backuprestore', 'local_ucla');
}

$message = $deleterestoremsg . html_writer::empty_tag('br') . get_string('deleterestore', 'local_ucla');
$obj->message = $message;

// URL to backup the course.
$url = new moodle_url('/backup/backup.php', array('id' => $course->id));
$obj->url = (string)$url;

$obj->title = get_string('deleterestoretitle', 'local_ucla');
$obj->closeButtonTitle = get_string('close', 'editor');

header('Content-Type: application/json');
echo json_encode($obj);
