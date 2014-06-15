<?php
/**
 * UCLA Course Download
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/ucla_course_download/classes/base.php');
require_once($CFG->dirroot . '/blocks/ucla_course_download/classes/files.php');
global $CFG, $DB, $PAGE;

$courseid = required_param('courseid', PARAM_INT); // course ID

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('coursemisconf');
}
require_login($course);
$context = context_course::instance($course->id);
if (!has_capability('block/ucla_course_download:requestzip', $context)) {
    print_error('noaccess', 'block_ucla_course_download');
}

$PAGE->set_url('/blocks/ucla_course_download/view.php',
            array('courseid' => $courseid));

$page_title = $course->shortname . ': ' . get_string('pluginname',
                    'block_ucla_course_download');

$PAGE->set_context($context);
$PAGE->set_title($page_title);
$PAGE->set_heading($page_title);
$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-' . $course->format);

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
    if ($downloadoption == 'files' &&
            has_capability('moodle/course:manageactivities', $context)) {
        $content = $coursecontent->renderable_content();
        $body .= $output->instructor_file_contents_view($content);
    }
}

// Start output screen.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'block_ucla_course_download'), 2, 'headingblock');
echo $body;
echo $OUTPUT->footer();
