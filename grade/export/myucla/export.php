<?php  

require_once '../../../config.php';
require_once $CFG->dirroot.'/grade/export/lib.php';
require_once 'grade_export_myucla.php';

$id                = required_param('id', PARAM_INT); // course id
$groupid           = optional_param('groupid', 0, PARAM_INT);
$itemids           = required_param('itemids', PARAM_RAW);
$export_feedback   = optional_param('export_feedback', 0, PARAM_BOOL);
$updatedgradesonly = optional_param('updatedgradesonly', false, PARAM_BOOL);
$displaytype       = optional_param('displaytype', $CFG->grade_export_displaytype, PARAM_INT);
$decimalpoints     = optional_param('decimalpoints', $CFG->grade_export_decimalpoints, PARAM_INT);
$filetype          = optional_param('filetype', 'csv', PARAM_FILE);

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('nocourseid');
}

require_login($course);
$context = context_course::instance($id);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/myucla:view', $context);

// START UCLA MOD: CCLE-4659 - Migrate add to log calls for grade export
$event = \local_gradebook\event\grades_exported_myucla::create(array(
    'context' => $context,
    'other' => array('type' => 'myucla')    
));
$event->trigger();
// END UCLA MOD: CCLE-4659

// print all the exported data here
$export = new grade_export_myucla($course, $groupid, $itemids, $export_feedback, $updatedgradesonly, $displaytype, $decimalpoints, $filetype);
$export->print_grades();

