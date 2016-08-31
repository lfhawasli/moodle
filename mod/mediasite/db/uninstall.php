<?php
// Allows you to execute a PHP code before the plugin's database tables and data are dropped during the plugin uninstallation.
require_once(dirname(__FILE__) . '/../../../config.php');
require_once("$CFG->dirroot/mod/mediasite/locallib.php");

defined('MOODLE_INTERNAL') || die();

/**
 * This is called at the beginning of the uninstallation process to give the module
 * a chance to clean-up its hacks, bits etc. where possible.
 *
 * @return bool true if success
 */
function xmldb_mediasite_uninstall() {
	// figure out if the local plugin is installed, it must be removed first
	$localInstalled = is_local_mediasite_courses_installed();
	if ($localInstalled) {
		print_error(get_string('error_local_plugin_still_installed', 'mediasite'));
	}
    return !is_local_mediasite_courses_installed();
}
?>