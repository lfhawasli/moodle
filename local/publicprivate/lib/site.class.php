<?php
// This file is part of the UCLA local plugin for Moodle - http://moodle.org/
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
 * Handles site level public/private methods.
 *
 * @package    local_publicprivate
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class file.
 *
 * @package    local_publicprivate
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class PublicPrivate_Site {
    /**
     * Check if public/private is already enabled at the site level.
     *
     * @global object $CFG
     * @return boolean
     */
    public static function is_enabled() {
        global $CFG;

        return isset($CFG->enablepublicprivate) 
            && ($CFG->enablepublicprivate == 1);
    }

    /**
     * Checks to make sure that "Restrict by grouping" conditional activity is
     * active.
     *
     * @return boolean
     */
    public static function can_enable() {
        $enabledlist = core\plugininfo\availability::get_enabled_plugins();
        return in_array('availability_grouping', $enabledlist);
    }

    /**
     * Checks that the course table has the necessary columns.
     *
     * @return boolean
     */
    public static function is_installed() {
        global $DB;

        $a = false;
        $b = false;
        $c = false;

        foreach($DB->get_columns('course') as $col) {
            switch($col->name) {
                case 'enablepublicprivate': $a = true; break;
                case 'grouppublicprivate': $b = true; break;
                case 'groupingpublicprivate': $c = true; break;
            }
        }

        return $a && $b && $c;
    }

    /**
     * Adds the necessary columns to the course table.
     *
     * @throws PublicPrivate_Site_Exception
     */
    public static function install() {
        global $DB;

        if (PublicPrivate_Site::is_installed()) {
            throw new PublicPrivate_Site_Exception('Cannot install as public/private is already installed.');
        }

        $dbman = $DB->get_manager();

        $table = new xmldb_table('course');

        $enablepublicprivate = new xmldb_field('enablepublicprivate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'visibleold');
        $grouppublicprivate = new xmldb_field('grouppublicprivate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'enablepublicprivate');
        $groupingpublicprivate = new xmldb_field('groupingpublicprivate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'grouppublicprivate');

        if (!$dbman->field_exists($table, $enablepublicprivate)) {
            $dbman->add_field($table, $enablepublicprivate);
        }

        if (!$dbman->field_exists($table, $grouppublicprivate)) {
            $dbman->add_field($table, $grouppublicprivate);
        }

        if (!$dbman->field_exists($table, $groupingpublicprivate)) {
            $dbman->add_field($table, $groupingpublicprivate);
        }
    }
}
