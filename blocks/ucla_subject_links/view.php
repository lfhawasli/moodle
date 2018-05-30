<?php
/**
 *  The subject area link section, display the content of the htm file.
 **/

require_once(dirname(__FILE__).'/../../config.php');
global $CFG, $PAGE;

require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
require_once($CFG->dirroot.
    '/blocks/ucla_subject_links/block_ucla_subject_links.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');

$courseid = required_param('course_id', PARAM_INT); // course ID
$subjarea = required_param('subj_area', PARAM_FILE);// subject area

if (! $course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('coursemisconf');
}

$context = context_course::instance($courseid);

// Initialize $PAGE
$PAGE->set_url('/blocks/ucla_subject_links/view.php', 
    array('course_id' => $courseid, 'subj_area' => $subjarea));

$page_title = $course->shortname.': '.get_string('pluginname',
    'block_ucla_subject_links');

$PAGE->set_context($context);
$PAGE->set_title($page_title);
$PAGE->set_course($course);
$PAGE->set_heading($course->fullname);

$PAGE->set_pagelayout('base');

function callback_subject_area($buffer) {
    global $CFG;
    return (str_replace('%www_root%', $CFG->wwwroot . '/blocks/ucla_subject_links/content', $buffer));
}

echo $OUTPUT->header();

if (block_ucla_subject_links::subject_exist($course, $subjarea)) { 
    ob_start('callback_subject_area');
    readfile(block_ucla_subject_links::get_location() . $subjarea . '/index.htm');
    ob_end_flush();
} else {
    echo $OUTPUT->notification(get_string('error', 'block_ucla_subject_links'), 'notifywarning');
}               
            
echo $OUTPUT->footer();

// Log views.
$event = \block_ucla_subject_links\event\page_viewed::create(array(
    'courseid' => $courseid,
    'context'  => $context,
    'other'    => array(
        'subjarea' => $subjarea
    )
));
$event->trigger();

/** eof **/
