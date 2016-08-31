<?php
require_once($CFG->dirroot."/config.php");
require_once($CFG->dirroot.'/mod/mediasite/basiclti_lib.php');
require_once($CFG->dirroot.'/mod/mediasite/basiclti_locallib.php');

defined('MOODLE_INTERNAL') || die();

class mediasite_endpoint {
	const LTI_SEARCH = 0;
	const LTI_MY_MEDIASITE = 1;
	const LTI_CATALOG = 2;
	const LTI_LAUNCH = 3;
	const LTI_COVERPLAY = 4;
}

class mediasite_menu_placement {
	const SITE_PAGES = 0;
	const COURSE_MENU = 1;
}

function basiclti_mediasite_view($instance, $siteid, $endpointType, $mediasiteId = null) {
	global $USER;

	$typeconfig = basiclti_get_type_config($siteid);
	$endpoint = $typeconfig->endpoint;
	$roles = get_all_enrollments();

	if (!isset($typeconfig->lti_consumer_key)) {
		$inpopup = optional_param('inpopup', 0, PARAM_BOOL);
		redirect(new moodle_url('/mod/mediasite/error.php', array('inpopup' => $inpopup)));
	}

	switch($endpointType) {
		case mediasite_endpoint::LTI_SEARCH:
			$endpoint = $endpoint.'/LTI/';
			break;
		case mediasite_endpoint::LTI_MY_MEDIASITE:
			$endpoint = $endpoint.'/LTI/MyMediasite';
			break;
		case mediasite_endpoint::LTI_CATALOG:
			$endpoint = $endpoint.'/LTI/Catalog';
			break;
		case mediasite_endpoint::LTI_LAUNCH:
			if ($mediasiteId == null) {
				throw new moodle_exception('generalexceptionmessage', 'error', '', 'basiclti_mediasite_view was called without a value for $mediasiteId.');
			}
			$endpoint = $endpoint.'/LTI/Home/Launch?mediasiteId='.$mediasiteId;
			break;
		case mediasite_endpoint::LTI_COVERPLAY:
			if($mediasiteId == null) {
				throw new moodle_exception('generalexceptionmessage', 'error', '', 'basiclti_mediasite_view was called without a value for $mediasiteId.');
			}
			$endpoint = $endpoint.'/LTI/Home/Coverplay?mediasiteId='.$mediasiteId;
			break;
		default:
			throw new moodle_exception('generalexceptionmessage', 'error', '', 'basiclti_mediasite_view was called with an invalid value for the $endpointType argument.');
	}

	basiclti_view($instance, $siteid, $typeconfig, $endpoint, $roles);
}

function get_all_enrollments() {
	global $DB, $USER;

	$allEnrollments = array();

	$selectEnrolledCourses = '
		SELECT DISTINCT c.id, c.shortname, c.idnumber
		  FROM {user} u
		       INNER JOIN {role_assignments} ra ON ra.userid = u.id
		       INNER JOIN {context} ct ON ct.id = ra.contextid
		       INNER JOIN {course} c ON c.id = ct.instanceid
		 WHERE u.id = ?';
	$courseIds = $DB->get_records_sql($selectEnrolledCourses, array($USER->id));

	foreach($courseIds as $courseId) {
		// context_course::instance($course->id);
		$context = context_course::instance($courseId->id);
		// function basiclti_get_ims_role($user, $context)
		$role = basiclti_get_ims_role($USER, $context);
		array_push($allEnrollments, get_mediasite_formatted_role($role, $courseId->shortname));
	}
	return $allEnrollments;
}

function get_mediasite_formatted_role($imsRole, $courseIdentifier) {
	return $courseIdentifier.':'.$imsRole;
}