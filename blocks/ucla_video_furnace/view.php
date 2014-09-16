<?php
/**
 * Displays video furnace video links (chronologically). 
 */

require_once(dirname(__FILE__).'/../../config.php');
global $CFG, $DB;

require_once($CFG->dirroot.'/local/ucla/lib.php');
require_once($CFG->dirroot.'/blocks/ucla_video_furnace/lib.php');

$courseid = required_param('courseid', PARAM_INT); // course ID
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('coursemisconf');
}
require_login($course);
$context = context_course::instance($courseid, MUST_EXIST);

init_page($course, $courseid, $context);

echo $OUTPUT->header();

// Are we allowed to display this page?
if (is_enrolled($context) || has_capability('moodle/course:view', $context)) {
    display_video_furnace_contents($course);
} else {
    echo get_string('guest_not_allow', 'block_ucla_video_furnace');
}        

echo $OUTPUT->footer();
