<?php

require_once('../../config.php');
require_once('lib.php');

$course_id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id'=>$course_id), '*', MUST_EXIST);

require_login($course);

$PAGE->set_context(context_course::instance($course_id));
$PAGE->set_title(get_string('title', 'block_competencies') . ': ' . $course->fullname);
$PAGE->set_heading($course->fullname);
$PAGE->set_url(new moodle_url('/blocks/competencies/course.php', array('id' => $course_id)));
$PAGE->navbar->add(get_string('title', 'block_competencies'), new moodle_url('/blocks/competencies/course.php', array('id' => $course_id)));
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('title', 'block_competencies') . ': ' . $course->fullname);

$competencies = block_competencies_db::get_course_items($course_id);
echo block_competencies_course_view::render($competencies);

echo $OUTPUT->footer();
