<?php


if (!defined('AJAX_SCRIPT')) {
    define('AJAX_SCRIPT', true);
}
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/course/lib.php');

// Initialise ALL the incoming parameters here, up front.
$courseid   = required_param('courseId', PARAM_INT);
$class      = required_param('class', PARAM_ALPHA);
$field      = required_param('field', PARAM_ALPHA);
$id         = required_param('id', PARAM_INT);

$PAGE->set_url('/local/publicprivate/rest.php', array('courseId'=>$courseid,'class'=>$class));

//NOTE: when making any changes here please make sure it is using the same access control as course/mod.php !!

list($course, $cm) = get_course_and_cm_from_cmid($id, '', $courseid);
$coursecontext = context_course::instance($course->id);
$modcontext = context_module::instance($cm->id);

require_login($course, false, $cm);
require_sesskey();
require_capability('moodle/course:manageactivities', $modcontext);

if ($class === 'resource') {

    echo $OUTPUT->header(); // send headers

    switch ($field) {

        case 'public':

            require_once($CFG->dirroot.'/local/publicprivate/lib/course.class.php');
            $publicprivate_course = new PublicPrivate_Course($cm->course);

            if($publicprivate_course->is_activated()) {
                require_once($CFG->dirroot.'/local/publicprivate/lib/module.class.php');
                PublicPrivate_Module::build($cm)->disable();
            }
                        
            break;

        case 'private':

            require_once($CFG->dirroot.'/local/publicprivate/lib/course.class.php');
            $publicprivate_course = new PublicPrivate_Course($cm->course);

            if($publicprivate_course->is_activated()) {
                require_once($CFG->dirroot.'/local/publicprivate/lib/module.class.php');
                PublicPrivate_Module::build($cm)->enable();
            }

            break;
    }

    // Refresh cm_info.
    list($course, $cm) = get_course_and_cm_from_cmid($id, '', $courseid);

    // Get new availability HTML.
    $courserenderer = $PAGE->get_renderer('core', 'course');
    $availability = $courserenderer->course_section_cm_availability($cm);
    echo json_encode($availability);
}


