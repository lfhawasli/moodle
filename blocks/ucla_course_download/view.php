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

$file = new file($courseid, $USER->id);

/* FOR TESTING */
//$file->add_request();
//file::process_requests();

$PAGE->set_url('/blocks/ucla_course_download/view.php',
            array('courseid' => $courseid));

$page_title = $course->shortname . ': ' . get_string('pluginname',
                    'block_ucla_course_download');

$PAGE->set_context(context_course::instance($courseid));
$PAGE->set_title($page_title);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-' . $course->format);

// Start output screen.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'block_ucla_course_download'), 2, 'headingblock');

echo $OUTPUT->heading(get_string('files', 'block_ucla_course_download'), 3);

$filestatus = $file->get_request_status();

switch($filestatus) {
    
    case 'request_completed':
        echo html_writer::tag('p', get_string('files_request_completed', 'block_ucla_course_download'));
        echo html_writer::tag('button', get_string('download', 'block_ucla_course_download'), array('class' => 'btn'));
        
        // TODO: update description with zip file information
        //       call $file->download_zip(); if user presses button
        
        break;
    case 'request_in_progress':
        echo html_writer::tag('p', get_string('files_requested', 'block_ucla_course_download'));
        echo html_writer::tag('button', get_string('request_in_progress', 'block_ucla_course_download'), array('class' => 'btn', 'class' => 'btn-disabled'));
        break;
    case 'request_available':
        echo html_writer::tag('p', get_string('files_not_requested', 'block_ucla_course_download'));
        echo html_writer::tag('button', get_string('request', 'block_ucla_course_download'), array('class' => 'btn'));
        
        // TODO: call $file->add_request(); if user presses button
        //       update page with in progress status?
        
        break;
    case 'request_unavailable':
        echo html_writer::tag('p', get_string('files_request_unavailable', 'block_ucla_course_download'));
    break;
}

echo $OUTPUT->heading(get_string('forum_posts', 'block_ucla_course_download'), 3);
echo html_writer::tag('p', get_string('forum_posts_not_requested', 'block_ucla_course_download'));
echo html_writer::tag('button', get_string('request', 'block_ucla_course_download'), array('class' => 'btn'));

/* else if users request is currently in progress
echo html_writer::tag('p', get_string('forum_posts_requested', 'block_ucla_course_download'));
echo html_writer::tag('button', get_string('request_in_progress', 'block_ucla_course_download'), array('class' => 'btn disabled'));

else zip file available for download
*/

echo $OUTPUT->heading(get_string('assignment_submissions', 'block_ucla_course_download'), 3);
echo html_writer::tag('p', get_string('submissions_not_requested', 'block_ucla_course_download'));
echo html_writer::tag('button', get_string('request', 'block_ucla_course_download'), array('class' => 'btn'));

/* else if users request is currently in progress
echo html_writer::tag('p', get_string('submissions_requested', 'block_ucla_course_download'));
echo html_writer::tag('button', get_string('request_in_progress', 'block_ucla_course_download'), array('class' => 'btn disabled'));

 else zip file available
*/
echo $OUTPUT->footer();
