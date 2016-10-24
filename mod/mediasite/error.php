<?php
require_once(dirname(__FILE__) . '/../../config.php');

// show an error message
global $PAGE, $OUTPUT;
// $context = context_system::instance();

// $PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/mod/mediasite/error.php');
$PAGE->set_title(get_string('mediasite', 'mediasite'));

$inpopup = optional_param('inpopup', 0, PARAM_BOOL);
if (!$inpopup) {
	$PAGE->set_pagelayout('popup');
} else {
	$PAGE->set_pagelayout('standard');
}
echo $OUTPUT->header();

echo html_writer::start_tag('div', array('class' => 'mform'));
echo html_writer::start_tag('div', array('class' => 'error'));
echo html_writer::tag('span', get_string('site_configuration_incomplete', 'mediasite'), array('class' => 'error'));
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

echo $OUTPUT->footer();
//echo 'BAD things man';

?>