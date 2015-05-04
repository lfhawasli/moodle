<?php
// This file is part of the UCLA support console for Moodle - http://moodle.org/
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
 * Upgrade code.
 *
 * @package    tool_uclasupportconsole
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
function xmldb_tool_uclasupportconsole_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2014050700) {
        // Remove 'Show 100 most recent MyUCLA grade log entries' report.
        $data = $DB->get_record('ucla_support_tools',
                array('name' => 'Show 100 most recent MyUCLA grade log entries'));
        if (!empty($data)) {
            $tool = \local_ucla_support_tools_tool::fetch($data);
            $tool->delete();
        }
    }

    return true;
}