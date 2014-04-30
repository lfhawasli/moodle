<?php
/**
 * Checks if a course has any additional content given a course id.
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/report/uclastats/reports/active_instructor_focused.php');

global $DB, $USER;

$obj = new stdClass();
$obj->status = false;

try {
    
    $course = $DB->get_record('course', array('id' => required_param('courseid', PARAM_INT)), '*', MUST_EXIST);
    $activeinstructorfocused = new active_instructor_focused($USER);
    
    if ($activeinstructorfocused->has_additional_course_content($course)) {

        $config = new stdClass;
        $config->title = get_string('deletecoursecontenttitle', 'local_ucla');
        $config->yesLabel = get_string('deletecoursecontentyes', 'local_ucla');
        $config->noLabel = get_string('deletecoursecontentno', 'local_ucla');;
        $config->closeButtonTitle = get_string('close', 'editor');
        $config->question = get_string('deletecoursecontentwarning', 'local_ucla',
                array('shortname' => $course->shortname, 'fullname' => $course->fullname));

        $url = new moodle_url('/backup/backup.php', array('id' => $course->id));
        $config->url = (string) $url;

        // Set status
        $obj->status = true;
        $obj->config = $config;
        
    } 
    
} catch (Exception $e) {
    // Both get_record and required_param will fail with exceptions with an invalid courseid.
    $obj->status = false;
}

// Print JSON obj.
header('Content-Type: application/json');
echo json_encode($obj);
