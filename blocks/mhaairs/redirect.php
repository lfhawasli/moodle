<?php

require_once('../../config.php');
require_once($CFG->dirroot.'/blocks/mhaairs/block_mhaairs_util.php');

$url = $_GET['url'];
$url = mh_hex_decode($url);
$service_id = $_GET['id'];
$service_id = mh_hex_decode($service_id);

session_start();
$course = $_SESSION['course'];

$courseid = empty($course->idnumber) ? $course->id : $course->idnumber;
$context = context_course::instance($course->id);
$rolename = null;
if ($roles = get_user_roles($context, $USER->id)) {
	foreach ($roles as $role) {
		$rolename = empty($role->name) ? $role->shortname : $role->name;
		if ($rolename == 'teacher' || $rolename == 'editingteacher') {
			$rolename = 'instructor';
			break;
		}
	}
	if ($rolename != null && $rolename != 'instructor') {
		$rolename = 'student';
	}
}
$token = mh_create_token2($CFG->block_mhaairs_customer_number,
                            $USER->username,
							urlencode($USER->firstname.' '.$USER->lastname),
                            $courseid,
							$course->id,
                            $service_id,
							$rolename,
							urlencode($course->shortname));
$encoded_token = mh_encode_token2($token, $CFG->block_mhaairs_shared_secret);

$url = new moodle_url($url, array('token' => $encoded_token));
//echo $url;
header('location: '.$url);