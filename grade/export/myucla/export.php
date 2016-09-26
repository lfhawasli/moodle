<?php

require_once '../../../config.php';
require_once $CFG->dirroot.'/grade/export/lib.php';
require_once 'grade_export_myucla.php';
require_once 'grade_export_form_myucla.php';

$id                = required_param('id', PARAM_INT); // course id
$PAGE->set_url('/grade/export/myucla/export.php', array('id'=>$id));

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('nocourseid');
}

require_login($course);
$context = context_course::instance($id);
$groupid = groups_get_course_group($course, true);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/myucla:view', $context);

// We need to call this method here before any print otherwise the menu won't display.
// If you use this method without this check, will break the direct grade exporting (without publishing).
$key = optional_param('key', '', PARAM_RAW);
if (!empty($CFG->gradepublishing) && !empty($key)) {
    print_grade_page_head($COURSE->id, 'export', 'myucla', get_string('exportto', 'grades') . ' ' . get_string('pluginname', 'gradeexport_myucla'));
}

if (groups_get_course_groupmode($COURSE) == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
    if (!groups_is_member($groupid, $USER->id)) {
        print_error('cannotaccessgroup', 'grades');
    }
}

$params = array(
    'includeseparator'=>true,
    'publishing' => true,
    'simpleui' => true,
    'multipledisplaytypes' => true
);
$mform = new grade_export_form_myucla(null, $params);
$data = $mform->get_data();
$export = new grade_export_myucla($course, $groupid, $data);

// If the gradepublishing is enabled and user key is selected print the grade publishing link.
if (!empty($CFG->gradepublishing) && !empty($key)) {
    groups_print_course_menu($course, 'index.php?id='.$id);
    echo $export->get_grade_publishing_url();
    echo $OUTPUT->footer();
} else {
    $export->print_grades();
}
