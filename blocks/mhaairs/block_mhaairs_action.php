<?php
define('AJAX_SCRIPT', true);
define('NO_DEBUG_DISPLAY', true);
require_once('../../config.php');
require_once($CFG->libdir.'/enrollib.php');
require_once($CFG->dirroot.'/blocks/mhaairs/block_mhaairs_util.php');

$secure = '';
if (isset($_SERVER['HTTPS'])) {
    $secure   = filter_var($_SERVER['HTTPS'], FILTER_SANITIZE_STRING);
}
$token    = optional_param('token'   , 'test', PARAM_ALPHANUM);
$action   = optional_param('action'  , null  , PARAM_ALPHA);
$username = optional_param('username', null  , PARAM_USERNAME);
$userid   = optional_param('userid'  , null  , PARAM_INT);
$password = optional_param('password', null  , PARAM_TEXT);

if((strcasecmp($secure, 'on') == 0) && !empty($CFG->block_mhaairs_sslonly)) {
    echo 'Connection must be secured with SSL';
    return;
}

$secret = !empty($CFG->block_mhaairs_shared_secret) ? $CFG->block_mhaairs_shared_secret : '';

$result = null;

switch($action){
    case "test"             :    $result = "OK"; break;
    case "ValidateLogin"    :    $result = mh_validate_login($token, $secret, $username, $password); break;
    case "GetUserInfo"      :    $result = mh_get_user_info($token, $secret); break;
	case "GetServerTime"	:	 $result = mh_get_time_stamp(); break;
    default : break;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result);
