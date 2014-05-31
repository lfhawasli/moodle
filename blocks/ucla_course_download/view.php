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

if (isset($_GET['action']) && $action = $_GET['action']) {
    switch ($action) {

        /* FOR TESTING CRON */
        case 'process_requests':
            global $DB;

            $requests = $DB->get_records('ucla_archives', array("type" => 'files'));

            foreach ($requests as $request) {
                // Delete zip if old request.
                if( block_ucla_course_download_file::is_old($request) ) {
                    block_ucla_course_download_file::delete_zip($request);
                    continue;
                }

                $coursecontentrequest = new block_ucla_course_download_file($request->courseid, $request->userid);

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

echo $OUTPUT->heading(get_string('files', 'block_ucla_course_download'), 3);

$coursecontentstatus = $coursecontent->get_request_status($timerequested, $timeupdated);

echo html_writer::start_tag('form', array('action' => new moodle_url('/blocks/ucla_course_download/view.php',array('courseid' => $courseid)), 'method' => 'get'));
echo html_writer::tag('input', '',array('type'=> 'hidden', 'name' => 'courseid','value' =>$courseid));

$fs = get_file_storage();

switch($coursecontentstatus) {
    case 'request_completed':
        // Format times.
        $timeupdatedstring = userdate($timeupdated);
        $timedeletedstring = userdate($timerequested + 2592000); // TODO: Make config.

        $requestmessage = get_string('request_completed', 'block_ucla_course_download', 'course files') . ' ' .
                          get_string('request_completed_updated', 'block_ucla_course_download', $timeupdatedstring) . ' '.
                          get_string('request_completed_deletion', 'block_ucla_course_download', $timedeletedstring);
        echo html_writer::tag('p', $requestmessage);

        $request = $coursecontent->get_request();
        $file = $fs->get_file_by_id($request->fileid);
        $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());

        echo html_writer::link($url, get_string('download', 'block_ucla_course_download'), array('class' => 'btn btn-primary'));
        break;
    case 'request_in_progress':
        echo html_writer::tag('p', get_string('request_in_progress', 'block_ucla_course_download', 'course files'));
        echo html_writer::tag('button', get_string('in_progress', 'block_ucla_course_download'), array('class' => 'btn', 'disabled' => 'true'));
        break;
    case 'request_available':
        echo html_writer::tag('p', get_string('not_requested', 'block_ucla_course_download', 'course files'));
        echo html_writer::tag('button', get_string('request', 'block_ucla_course_download'), array('class' => 'btn', 'name' => 'action', 'value' => 'files_request'));

        break;
    case 'request_unavailable':
        echo html_writer::tag('p', get_string('request_unavailable', 'block_ucla_course_download', 'course files'));
    break;
}

echo html_writer::end_tag('form');

echo $OUTPUT->footer();
