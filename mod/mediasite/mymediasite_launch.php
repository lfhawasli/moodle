<?php
require_once("../../config.php");
require_once($CFG->dirroot.'/mod/mediasite/basiclti_lib.php');
require_once($CFG->dirroot.'/mod/mediasite/basiclti_locallib.php');
require_once($CFG->dirroot.'/mod/mediasite/basiclti_mediasite_lib.php');

$id = optional_param('id', 1, PARAM_INT); // Course ID
$siteid = required_param('siteid', PARAM_INT); // Site ID

if ($id && $id > 0) {
    // if (!$cm = $DB->get_record("course_modules", array("id" => $id)) {
    //     throw new moodle_exception('generalexceptionmessage', 'error', '', 'Course Module ID was incorrect');
    // }

    if (! $course = $DB->get_record("course", array("id" => $id))) {
        throw new moodle_exception('generalexceptionmessage', 'error', '', 'Course is misconfigured');
    }

    // if (! $basiclti = $DB->get_record("mediasite", array("id" => $cm->instance))) {
    //     throw new moodle_exception('generalexceptionmessage', 'error', '', 'Course module is incorrect '.$cm->instance);
    // }
}

require_login($course);

// add_to_log($course->id, "mediasite", "basiclti_launch", "basiclti_launch.php?id=$id", "$basiclti->id");

basiclti_mediasite_view($course, $siteid, mediasite_endpoint::LTI_MY_MEDIASITE);