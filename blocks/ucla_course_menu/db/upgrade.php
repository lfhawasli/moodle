<?php
// This file is part of the UCLA course menu block for Moodle - http://moodle.org/
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
 * Contains XML database block upgrade function.
 *
 * @package block_ucla_course_menu
 * @copyright 2016 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * XML database block upgrade function.
 *
 * @param version $oldversion
 * @param block $block
 * @return boolean
 */
function xmldb_block_ucla_course_menu_upgrade($oldversion, $block) {
    global $DB, $CFG;

    // CCLE-7279 - Remove Course Menu Block.
    if ($oldversion < 2018032200) {
        $blockinstances = $DB->get_recordset('block_instances',
                array('blockname' => 'ucla_course_menu',));
        // If atleast one block instance exists, delete them.
        if ($blockinstances->valid()) {
            foreach($blockinstances as $blockinstance) {
                blocks_delete_instance($blockinstance);
            }
        }
        $blockinstances->close();
        
        // Savepoint reached.
        upgrade_block_savepoint(true, 2018032200, 'ucla_course_menu');
    }
    return true;
}

