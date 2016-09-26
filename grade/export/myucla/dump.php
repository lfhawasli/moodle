<?php

define('NO_MOODLE_COOKIES', true); // session not used here
require_once '../../../config.php';
require_once($CFG->dirroot.'/grade/export/myucla/grade_export_myucla.php');

$id                 = required_param('id', PARAM_INT);
$groupid            = optional_param('groupid', 0, PARAM_INT);
$groupingid         = optional_param('groupingid', 0, PARAM_INT);
$itemids            = required_param('itemids', PARAM_RAW);
$exportfeedback     = optional_param('export_feedback', 0, PARAM_BOOL);
$separator          = optional_param('separator', 'comma', PARAM_ALPHA);
$displaytype        = optional_param('displaytype', $CFG->grade_export_displaytype, PARAM_RAW);
$decimalpoints      = optional_param('decimalpoints', $CFG->grade_export_decimalpoints, PARAM_INT);
$onlyactive         = optional_param('export_onlyactive', 0, PARAM_BOOL);

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('nocourseid');
}

require_user_key_login('grade/export', $id); // we want different keys for each course

if (empty($CFG->gradepublishing)) {
    print_error('gradepubdisable');
}

$context = context_course::instance($id);
require_capability('moodle/grade:export', $context);
require_capability('gradeexport/myucla:publish', $context);
require_capability('gradeexport/myucla:view', $context);

if (!groups_group_visible($groupid, $COURSE)) {
    print_error('cannotaccessgroup', 'grades');
}

$formdata = grade_export::export_bulk_export_data($id, $itemids, $exportfeedback, $onlyactive, $displaytype,
    $decimalpoints, null, $separator, $groupingid);

$export = new grade_export_myucla($course, $groupid, $formdata);
$export->print_grades();


