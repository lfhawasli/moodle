<?php
// This file is part of the local UCLA plugin for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * CLI script to uninstall one or more plugins.
 *
 * @package    local_ucla
 * @copyright  2014 UCLA regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/clilib.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array('help' => false),
        array('h' => 'help'));

if ($options['help'] || empty($unrecognized)) {
    $help = "CLI script to uninstall one or more plugins.

Pass in a plugin name, or several separated by commas, and the script will ask
for a confirmation and then will uninstall those plugins.

Options:
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php local/ucla/cli/uninstall_plugins.php [csv of plugins]
";
    cli_error($help);
}

// Need to be able to support Moodle 2.5 and 2.7.
$pluginman = null;
if (!class_exists('core_plugin_manager')) {
    require_once($CFG->libdir . '/pluginlib.php');
    $pluginman = plugin_manager::instance();
} else {
    $pluginman = core_plugin_manager::instance();
}

// Get list of plugins and make sure they all exist.
$pluginscsv = reset($unrecognized);
$pluginnames = explode(',', $pluginscsv);
$plugins = array();
foreach ($pluginnames as $pluginname) {
    // Make sure that plugins exists and we can uninstall.
    $pluginfo = $pluginman->get_plugin_info($pluginname);

    if (is_null($pluginfo)) {
        cli_problem("WARNING: Cannot find '$pluginname' plugin.");
        continue;
    } else if ($pluginfo->get_status() === $pluginman::PLUGIN_STATUS_NEW) {
        // Plugin has already been uninstalled.
        cli_problem("WARNING: Plugin '$pluginname' is already uninstalled.");
        continue;
    } else if (!$pluginman->can_uninstall_plugin($pluginfo->component)) {
        cli_error("Cannot uninstall '$pluginname' plugin.");
    }

    $plugins[] = $pluginfo;
}

if (empty($plugins)) {
    cli_error("No plugins to uninstall.");
}

// Now ask for confirmation.
echo "Are you sure you want to uninstall the following plugins?\n";
foreach ($plugins as $plugin) {
    echo $plugin->displayname . " (" . $plugin->component . ")\n";
}
$prompt = get_string('cliyesnoprompt', 'admin');
$input = cli_input($prompt, '', array(get_string('clianswerno', 'admin'), get_string('cliansweryes', 'admin')));
if ($input == get_string('clianswerno', 'admin')) {
    exit(1);
}

// Confirmed, so go ahead and report process.
$progress = new text_progress_trace();
foreach ($plugins as $plugin) {
    $progress->output("Uninstalling " . $plugin->displayname . " (" . $plugin->component . ")");
    $pluginman->uninstall_plugin($plugin->component, $progress);
}
$progress->output('Done.');
