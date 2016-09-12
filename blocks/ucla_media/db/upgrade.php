<?php
// This file is part of the UCLA Media block for Moodle - http://moodle.org/
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
 * Upgrades database for bruincast
 *
 * @package    block_ucla_media
 * @author     Anant Mahajan
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade function.
 *
 * @param int $oldversion
 * @return boolean
 */
function xmldb_block_ucla_media_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();
    if ($oldversion < 2016082300) {
        $table = new xmldb_table('ucla_bruincast');
        $indexes[] = new xmldb_field('audio_url', XMLDB_TYPE_TEXT, null, null, null, null, null, 'bruincast_url');
        $indexes[] = new xmldb_field('podcast_url', XMLDB_TYPE_TEXT, null, null, null, null, null, 'audio_url');
        $indexes[] = new xmldb_field('week', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'podcast_url');
        $indexes[] = new xmldb_field('name', XMLDB_TYPE_TEXT, null, null, null, null, null, 'week');

        foreach ($indexes as $index) {
            if (!$dbman->field_exists($table, $index)) {
                $dbman->add_field($table, $index);
            }
        }
        upgrade_block_savepoint(true, 2016082300, 'block_ucla_media');
    }

    return true;
}