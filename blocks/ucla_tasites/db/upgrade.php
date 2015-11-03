<?php
// This file is part of the UCLA TA sites block for Moodle - http://moodle.org/
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
 * Upgrade code for the UCLA TA site creator block.
 *
 * @package    block_ucla_tasites
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/ucla_tasites/block_ucla_tasites.php');

/**
 * Execute block upgrade from the given older version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_block_ucla_tasites_upgrade($oldversion) {
    global $DB;

    // New site type for TA sites. Changing from "instruction" to "tasite".
    if ($oldversion < 2013021900) {
        // Get list of all current TA sites by doing the following:
        // 1) Get all sites labeled as "instruction".
        // 2) For each site, call block_ucla_tasites::is_tasite.
        // 3) If true, then change type to tasite.

        // 1) Get all sites labeled as "instruction".
        $instructionsites = $DB->get_recordset('ucla_siteindicator',
                array('type' => 'instruction'));

        if ($instructionsites->valid()) {
            // 2) For each site, call block_ucla_tasites::is_tasite.
            foreach ($instructionsites as $site) {
                if (block_ucla_tasites::is_tasite($site->courseid)) {
                    // 3) If true, then change type to tasite.
                    // NOTE: No need to do siteindicator_site->set_type, because
                    // the role grouping hasn't changed.
                    $site->type = 'tasite';
                    $DB->update_record('ucla_siteindicator', $site, true);
                }
            }
        }

        // Migration complete.
        upgrade_block_savepoint(true, 2013021900, 'ucla_tasites');
    }
}
