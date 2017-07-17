<?php
// This file is part of the UCLA Site Invitation Plugin for Moodle - http://moodle.org/
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
 * Post-installation code.
 *
 * @package    enrol_invitation
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/enrollib.php');

/**
 * Add site invitation to every course on the system.
 *
 * @see upgrade_plugins_modules()
 */
function xmldb_enrol_invitation_install() {
    global $DB;

    // Enable site invitation enrollment plugin
    // see /admin/enrol.php.
    $enabled = enrol_get_plugins(true);
    $enabled = array_keys($enabled);
    $enabled[] = 'invitation';
    set_config('enrol_plugins_enabled', implode(',', $enabled));
    $syscontext = context_system::instance();
    $syscontext->mark_dirty(); // Resets all enrol caches.

    // Install site invitation plugin for every course on the system.
    // NOTE: use get_recordset instead of get_records because system might have
    // very many courses and loading them all into memory would crash the system.
    // See http://docs.moodle.org/dev/Datalib_Notes
    // and http://docs.moodle.org/dev/Data_manipulation_API#Using_Recordsets.
    $invitation = enrol_get_plugin('invitation');
    $coursesrecords = $DB->get_recordset('course');
    foreach ($coursesrecords as $course) {
        // Make sure that we aren't adding the SITEID.
        if ($course->id == SITEID) {
            continue;
        }

        $instanceid = $invitation->add_instance($course);
        if (is_null($instanceid)) {
            debugging('Cannot add enrol plugin for courseid ' . $course->id);
        }
    }
    $coursesrecords->close();
}