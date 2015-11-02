<?php
// This file is part of the UCLA support tools plugin for Moodle - http://moodle.org/
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
 * Upgrade for plugin changes.
 *
 * @package    local_ucla_support_tools
 * @copyright  2015 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade function.
 *
 * @param int $oldversion
 * @return boolean
 */
function xmldb_local_ucla_support_tools_upgrade($oldversion) {
    global $DB, $CFG;

    $dbman = $DB->get_manager();

    if ($oldversion < 2015110200) {
        // Add tools to Registrar Data category.
        $registrarcat = null;

        // Find it.
        $categories = \local_ucla_support_tools_category::fetch_all();
        foreach ($categories as $category) {
            if ($category->name == 'Registrar data') {
                $registrarcat = $category;
                break;
            }
        }

        if (empty($registrarcat)) {
            // Not found, create it.
            $data = array('name' => 'Registrar data', 'color' => '#0EB8F1');
            $registrarcat = \local_ucla_support_tools_category::create($data);
        }

        // Add two new reports: ccle_classcalendar and ucla_get_course_srs.
        $reports = array('ccle_classcalendar', 'ucla_get_course_srs');
        try {
            foreach ($reports as $report) {
                $data = array('url' => '/' . $CFG->admin . '/tool/uclasupportconsole/index.php#' . $report,
                    'name' => get_string($report, 'tool_uclasupportconsole'));
                $tool = \local_ucla_support_tools_tool::create($data);
                $registrarcat->add_tool($tool);
            }
        } catch (Exception $ex) {
            // Ignore any reports we cannot add, because they exists already.
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2015110200, 'local', 'ucla_support_tools');
    }

    return true;
}