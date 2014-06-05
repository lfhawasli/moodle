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

$PAGE->set_context(context_course::instance($courseid));
$PAGE->set_title($page_title);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-' . $course->format);

$coursecontent = new block_ucla_course_download_files($courseid, $USER->id);

$action = optional_param('action', '', PARAM_ALPHAEXT);

switch ($action) {

    /* FOR TESTING CRON */
    case 'process_requests':
        global $DB;

        $requests = $DB->get_records('ucla_archives', array("type" => 'files'));

        foreach ($requests as $request) {
            // Delete zip if old request.
            if( block_ucla_course_download_files::is_old($request) ) {
                block_ucla_course_download_files::delete_zip($request);
                continue;
            }

            $coursecontentrequest = new block_ucla_course_download_files($request->courseid, $request->userid);

            $msg ="processing request " . $request->id ."  ";
            print_object($msg);

            $coursecontentrequest->process_request($request);
        }

        break;
    /* END TESTING */

    case 'files_request':
        $coursecontent->add_request();
        break;
    case 'files_download':
        $coursecontent->download_zip();
        break;
    case 'posts_request':
    case 'posts_download':
    case 'submissions_request':
    case 'submissions_download':
}


// Start output screen.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'block_ucla_course_download'), 2, 'headingblock');

/* FOR TESTING CRON */
echo html_writer::start_tag('form', array('action' => new moodle_url('/blocks/ucla_course_download/view.php',array('courseid' => $courseid)), 'method' => 'get'));
echo html_writer::tag('input', '',array('type'=> 'hidden', 'name' => 'courseid','value' =>$courseid));
echo html_writer::tag('button', 'Process requests', array('class' => 'btn', 'type'=>'submit', 'name' => 'action', 'value' =>'process_requests'));
echo html_writer::end_tag('form');
/* END TESTING */

$coursecontentstatus = $coursecontent->get_request_status();

// Get plugin renderer.

/** var $output block_ucla_course_download_renderer */
$output = $PAGE->get_renderer('block_ucla_course_download');

// Print title.
echo $output->title_heading(get_string('files', 'block_ucla_course_download'));

// Forward calls to renderer.
echo call_user_func(array($output, $coursecontentstatus), $coursecontent);


echo $OUTPUT->footer();
