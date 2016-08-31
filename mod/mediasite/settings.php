<?php  //$Id: settings.php,v 1.1.2.3 2008/01/24 20:29:36 skodak Exp $

require_once(dirname(__FILE__) . '/../../config.php');
require_once("$CFG->dirroot/mod/mediasite/lib.php");
require_once("$CFG->dirroot/mod/mediasite/locallib.php");

defined('MOODLE_INTERNAL') || die();

$modsettingmediasiteurl = new moodle_url('/mod/mediasite/site/configuration.php?section=modmediasite');

if (strpos(strtolower($_SERVER["REQUEST_URI"]), 'mediasite/site/configuration.php')
	|| strpos(strtolower($_SERVER["REQUEST_URI"]), 'mediasite/site/add.php')
	|| strpos(strtolower($_SERVER["REQUEST_URI"]), 'mediasite/site/edit.php')) {

	$settings = new admin_externalpage('activitysettingmediasite',
	    get_string('pluginname', 'mediasite'),
	    $modsettingmediasiteurl,
	    'mod/mediasite:addinstance');

} else {
	$settings->add(new admin_setting_heading('name', get_string('admin_settings_header', 'mediasite'), get_string('admin_settings_body', 'mediasite', $CFG->wwwroot.'/mod/mediasite/site/configuration.php?section=modmediasite').'<script type="text/javascript">window.location.href="'.$modsettingmediasiteurl.'";</script>'));
	// redirect($modsettingmediasiteurl);
}

?>
