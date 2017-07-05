<?php
// This file is part of the UCLA browse-by plugin for Moodle - http://moodle.org/
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
 * Upgrades the browse-by plug-in version.
 *
 * @package    block_ucla_browseby
 * @copyright  2016 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/blocks/ucla_browseby/db/install.php');

/**
 * Upgrader.
 *
 * @param int $oldversion
 * @return boolean
 */
function xmldb_block_ucla_browseby_upgrade($oldversion = 0) {
    global $CFG, $PAGE, $SITE, $DB;

    $dbman = $DB->get_manager();

    $result = true;

    if ($result && $oldversion < 2012032703) {
        xmldb_block_ucla_browseby_install();
    }

    if ($oldversion < 2016111800) {

        // Define index uid (unique) to be added to ucla_browseall_instrinfo.
        $table = new xmldb_table('ucla_browseall_instrinfo');
        $index = new xmldb_index('uid', XMLDB_INDEX_NOTUNIQUE, array('uid'));

        // Conditionally launch add index uid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Ucla_browseby savepoint reached.
        upgrade_block_savepoint(true, 2016111800, 'ucla_browseby');
    }

    // This adds an instance of this block to the site page if it
    // doesn't already exist.
    block_ucla_browseby::add_to_frontpage();

    return $result;
}
