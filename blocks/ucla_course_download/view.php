<?php
/**
 * UCLA Course Download
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/ucla_course_download/classes/course_content.class.php');
require_once($CFG->dirroot . '/blocks/ucla_course_download/classes/file.class.php');
global $CFG, $DB, $PAGE;

$courseid = required_param('courseid', PARAM_INT); // course ID

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('coursemisconf');
}
require_login($course);

$PAGE->set_url('/blocks/ucla_course_download/view.php',
            array('courseid' => $courseid));

$page_title = $course->shortname . ': ' . get_string('pluginname',
                    'block_ucla_course_download');

$PAGE->set_context(context_course::instance($courseid));
$PAGE->set_title($page_title);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-' . $course->format);

$file = new file($courseid, $USER->id);

// Start output screen.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'block_ucla_course_download'), 2, 'headingblock');

/* FOR TESTING CRON */
echo html_writer::start_tag('form', array('action' => new moodle_url('/blocks/ucla_course_download/view.php',array('courseid' => $courseid)), 'method' => 'get'));
echo html_writer::tag('input', '',array('type'=> 'hidden', 'name' => 'courseid','value' =>$courseid));
echo html_writer::tag('button', 'Process requests', array('class' => 'btn', 'type'=>'submit', 'name' => 'action', 'value' =>'process_requests'));
echo html_writer::end_tag('form');
/* END TESTING */

if (isset($_GET['action']) && $action = $_GET['action']) {
    switch ($action) {

        /* FOR TESTING CRON */
        case 'process_requests':
            global $DB;

            $requests = $DB->get_records('ucla_archives', array("type" => 'files'));

            foreach ($requests as $request) {
                // Delete zip if old request.
                if( file::is_old($request) ) {
                    file::delete_zip($request);
                    continue;
                }

                $filerequest = new file($request->courseid, $request->userid);

                $msg ="processing request " . $request->id ."  ";
                print_object($msg);

                $filerequest->process_request($request);
            }

            break;
        /* END TESTING */

        case 'files_request':
            $file->add_request();
            break;
        case 'files_download':
            $file->download_zip();
            break;
        case 'posts_request':
        case 'posts_download':
        case 'submissions_request':
        case 'submissions_download': 
    }
}

echo $OUTPUT->heading(get_string('files', 'block_ucla_course_download'), 3);

$filestatus = $file->get_request_status($timerequested, $timeupdated);

echo html_writer::start_tag('form', array('action' => new moodle_url('/blocks/ucla_course_download/view.php',array('courseid' => $courseid)), 'method' => 'get'));
echo html_writer::tag('input', '',array('type'=> 'hidden', 'name' => 'courseid','value' =>$courseid));

switch($filestatus) {

    case 'request_completed':
        // Format times.
        $timeupdatedstring = userdate($timeupdated);
        $timedeletedstring = userdate($timerequested + 2592000); // TODO: Make config.

        $requestmessage = get_string('request_completed', 'block_ucla_course_download', 'course files') . ' ' .
                          get_string('request_completed_updated', 'block_ucla_course_download', $timeupdatedstring) . ' '.
                          get_string('request_completed_deletion', 'block_ucla_course_download', $timedeletedstring);
        echo html_writer::tag('p', $requestmessage);
        echo html_writer::tag('button', get_string('download', 'block_ucla_course_download'), array('class' => 'btn', 'name' => 'action', 'value' =>'files_download'));
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

echo $OUTPUT->heading(get_string('forum_posts', 'block_ucla_course_download'), 3);
echo html_writer::tag('p', get_string('not_requested', 'block_ucla_course_download', "forum posts"));
echo html_writer::tag('button', get_string('request', 'block_ucla_course_download'), array('class' => 'btn'));

echo $OUTPUT->heading(get_string('assignment_submissions', 'block_ucla_course_download'), 3);
echo html_writer::tag('p', get_string('not_requested', 'block_ucla_course_download', "assignment submissions"));
echo html_writer::tag('button', get_string('request', 'block_ucla_course_download'), array('class' => 'btn'));

echo $OUTPUT->footer();
