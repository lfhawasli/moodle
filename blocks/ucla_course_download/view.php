<?php
/**
 * UCLA Course Download
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/ucla_course_download/classes/base.php');
require_once($CFG->dirroot . '/blocks/ucla_course_download/classes/files.php');
require_once($CFG->dirroot . '/blocks/ucla_course_download/locallib.php');

// Maybe courseid is passed as just "id".
$courseid = optional_param('id', null, PARAM_INT);
if (empty($courseid)) {
    $courseid = required_param('courseid', PARAM_INT); // course ID
}
if (!$course = get_course($courseid)) {
    print_error('coursemisconf');
}
// Make sure user is logged in and if not, prompt them to login.
require_login($course);
if (!isloggedin() || isguestuser()) {
    prompt_login($PAGE, $OUTPUT, $CFG, $course);
    die();
}

$context = context_course::instance($course->id);
// Make sure user has capability to request/download zips.
if (!has_capability('block/ucla_course_download:requestzip', $context)) {
    print_error('noaccess', 'block_ucla_course_download');
}

// If user is a student, then make sure they are allowed to download.
if (!has_capability('moodle/course:manageactivities', $context)) {
    if (!student_zip_requestable($course)) {
        print_error('noaccess', 'block_ucla_course_download');
    }
}

$PAGE->set_url('/blocks/ucla_course_download/view.php',
            array('courseid' => $courseid));

$pagetitle = get_string('coursedownload', 'block_ucla_course_download');

$PAGE->set_context($context);
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);
$PAGE->set_pagelayout('base');

// Get plugin renderer.
/** var $output block_ucla_course_download_renderer */
$output = $PAGE->get_renderer('block_ucla_course_download');

// List of available classes that handle the downloading of course content.
// Edit this array if adding in additional classes.
$downloadoptions = array('files');
$body = '';
foreach ($downloadoptions as $downloadoption) {
    // Load class.
    $classname = 'block_ucla_course_download_' . $downloadoption;
    $coursecontent = new $classname($courseid, $USER->id);

    // Do we need to repond to any new requests?
    $action = optional_param('action', null, PARAM_ALPHANUMEXT);
    if (!empty($action) && $action == $downloadoption.'_request') {
        $coursecontent->add_request();
        // Need to redirect to success message.
        redirect($PAGE->url);
    }

    // Print title.
    $body .= $output->title_heading(get_string($downloadoption, 'block_ucla_course_download'));

    $coursecontentstatus = $coursecontent->get_request_status();
    // Forward calls to renderer.
    $body .= $output->course_download_status($coursecontentstatus, $downloadoption, $coursecontent);

    // Files have a special display for instructors to show listing.
    if ($coursecontentstatus != 'request_unavailable' &&
            $downloadoption == 'files' &&
            has_capability('moodle/course:manageactivities', $context)) {
        $content = $coursecontent->renderable_content();
        $body .= $output->instructor_file_contents_view($content);
    }
}

// Start output screen.
echo $OUTPUT->header();

// Notify admin if students do not have access to this tool.
if (!student_zip_requestable($course)) {
    echo $OUTPUT->notification(get_string('studentaccessdisabled', 'block_ucla_course_download'), 'info');
}

echo $body;
echo $OUTPUT->footer();

// Log views.
$event = \block_ucla_course_download\event\filelist_viewed::create(array(
    'context' => $context
));
$event->trigger();