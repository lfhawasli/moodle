<?php
/**
 * UCLA Course Download
 */
require_once(dirname(__FILE__) . '/../../config.php');
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

// Start output screen.
// TODO: description and button content should be based on zip file status
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'block_ucla_course_download'), 2, 'headingblock');

echo $OUTPUT->heading(get_string('files', 'block_ucla_course_download'), 3);

echo html_writer::tag('p', get_string('files_not_requested', 'block_ucla_course_download'));
echo html_writer::tag('button', get_string('request', 'block_ucla_course_download'), array('class' => 'btn'));

echo $OUTPUT->heading(get_string('forum_posts', 'block_ucla_course_download'), 3);
echo html_writer::tag('p', get_string('forum_posts_not_requested', 'block_ucla_course_download'));
echo html_writer::tag('button', get_string('request', 'block_ucla_course_download'), array('class' => 'btn'));

echo $OUTPUT->heading(get_string('assignment_submissions', 'block_ucla_course_download'), 3);
echo html_writer::tag('p', get_string('submissions_not_requested', 'block_ucla_course_download'));
echo html_writer::tag('button', get_string('request', 'block_ucla_course_download'), array('class' => 'btn'));

echo $OUTPUT->footer();
