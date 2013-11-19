<?php
/**
 * UCLA Manage copyright status
 *
 * @package    ucla
 * @subpackage ucla_copyright_status
 * @copyright  2012 UC Regents    
 * @author     Jun Wan <jwan@humnet.ucla.edu>                                         
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later 
 */
require_once(dirname(__FILE__) . '/../../config.php');
global $CFG, $DB;

require_once($CFG->dirroot . '/lib/moodlelib.php');
require_once($CFG->dirroot . '/lib/accesslib.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_copyright_status/lib.php');
//require_once($CFG->dirroot . '/local/ucla/lib.php');

$courseid = required_param('courseid', PARAM_INT); // course ID
$action = optional_param('action_edit', null, PARAM_TEXT);
$filter = optional_param('filter_copyright', null, PARAM_TEXT);
$cancelled = optional_param('action_cancel', null, PARAM_TEXT);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('coursemisconf');
}
require_login($course);

$context = context_course::instance($courseid);
if (!has_capability('moodle/course:manageactivities', $context)) {
    print_error('permission_not_allow', 'block_ucla_copyright_status');
}

if (isset($cancelled)) {
    redirect(new moodle_url('/course/view.php', array('id' => $courseid)));
}

init_copyright_page($course, $courseid, $context);
set_editing_mode_button();

// Start output screen.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'block_ucla_copyright_status'),
        2, 'headingblock');

// If javascript disabled.
echo html_writer::tag('noscript',
        get_string('javascriptdisabled', 'block_ucla_copyright_status'),
        array('id' => 'block-ucla-copyright-status-noscript'));

if (isset($action)) {
    $data = data_submitted();
    update_copyright_status($data);
    echo $OUTPUT->notification(get_string('changessaved', 'block_ucla_copyright_status'), 'notifysuccess');
}

$filter = optional_param('filter_copyright', $CFG->sitedefaultlicense,
        PARAM_TEXT);
display_copyright_status_contents($courseid, $filter);
