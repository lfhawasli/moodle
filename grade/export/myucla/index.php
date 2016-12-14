<?php

require_once '../../../config.php';
require_once $CFG->dirroot.'/grade/export/lib.php';
require_once 'grade_export_myucla.php';
require_once 'grade_export_form_myucla.php';

$id = required_param('id', PARAM_INT); // course id

$PAGE->set_url('/grade/export/myucla/index.php', array('id'=>$id));

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('nocourseid');
}

require_login($course);
$context = context_course::instance($id);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/myucla:view', $context);

print_grade_page_head($COURSE->id, 'export', 'myucla', get_string('exportto', 'grades') . ' ' . get_string('pluginname', 'gradeexport_myucla'));
export_verify_grades($COURSE->id);

if (!empty($CFG->gradepublishing)) {
    $CFG->gradepublishing = has_capability('gradeexport/myucla:publish', $context);
}

$actionurl = new moodle_url('/grade/export/myucla/export.php');
$formoptions = array(
    'includeseparator'=>true,
    'publishing' => true,
    'simpleui' => true,
    'multipledisplaytypes' => true
);

$mform = new grade_export_form_myucla($actionurl, $formoptions);

$groupmode    = groups_get_course_groupmode($course);   // Groups are being used.
$currentgroup = groups_get_course_group($course, true);
if (($groupmode == SEPARATEGROUPS) &&
    (!$currentgroup) &&
    (!has_capability('moodle/site:accessallgroups', $context))) {

    echo $OUTPUT->heading(get_string("notingroup"));
    echo $OUTPUT->footer();
    die;
}

groups_print_course_menu($course, 'index.php?id='.$id);
echo '<div class="clearer"></div>';

$mform->display();

echo $OUTPUT->footer();

$event = \local_gradebook\event\grades_export_viewed_myucla::create(array(
    'context' => $context,
    'other' => array('type' => 'myucla')
));
$event->trigger();