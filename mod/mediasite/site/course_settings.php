<?php
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once("$CFG->dirroot/mod/mediasite/site/mod_course_settings_form.php");

global $OUTPUT, $CFG, $PAGE, $DB;

$id = required_param('id', PARAM_INT); // Course ID
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

$context = context_course::instance($id);
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/mod/mediasite/site/course_settings.php?id='.$id);

require_login($course, true);

require_capability('mod/mediasite:overridedefaults', $context);

$PAGE->set_pagelayout('incourse');
$pagetitle = strip_tags($course->shortname.': '.get_string('course_settings', 'mediasite'));
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);


$mform  = new Sonicfoundry\mod_course_settings_form(strval($course->id));

// persist the data if this is posting back
if ($mform->get_data()) {
	$data = $mform->get_data();
	$record = new stdClass();
	$isUpdate = $data->mediasite_course_config_id != '0';
	if ($isUpdate) {
		$record->id = $data->mediasite_course_config_id;
	}
	$record->course = $data->id;
	$record->mediasite_site = $data->mediasite_site;
	$site_show_integration_catalog = $DB->get_field('mediasite_sites', 'show_integration_catalog', array('id' => $data->mediasite_site));
	if ($site_show_integration_catalog == 3) {
		$record->mediasite_courses_enabled = 1;
	} else if ($site_show_integration_catalog == 0) {
		$record->mediasite_courses_enabled = 0;
	} else {
		$record->mediasite_courses_enabled = $data->mediasite_courses_enabled;
	}

	if ($isUpdate) {
		$DB->update_record('mediasite_course_config', $record);
	} else {
		$DB->insert_record('mediasite_course_config', $record);
	}
	redirect(new \moodle_url('/course/view.php', array('id' => $id)));
}



echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();
?>