<?php
// This file is part of Moodle - http://moodle.org/
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
 * Keeps track of upgrades to the UCLA format.
 *
 * @package    format_ucla
 * @copyright  2012 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrades plugins from old version.
 *
 * @param int $oldversion date of version to upgrade
 * @return bool
 */
function xmldb_format_ucla_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // If you installed it before we created install.xml.
    if ($oldversion < 2011051000) {
        // Define table ucla_course_prefs to be created.
        $table = new xmldb_table('ucla_course_prefs');

        // Adding fields to table ucla_course_prefs.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('value', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('timestamp', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);

        // Adding keys to table ucla_course_prefs.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table ucla_course_prefs.
        $table->add_index('searchindex', XMLDB_INDEX_UNIQUE, array('courseid', 'name'));

        // Conditionally launch create table for ucla_course_prefs.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Ucla savepoint reached.
        upgrade_plugin_savepoint(true, 2011051000, 'format', 'ucla');
    }

    // Update all courses using ucla format to have $course->coursedisplay set
    // to COURSE_DISPLAY_MULTIPAGE.
    if ($oldversion < 2012061701) {
        // Get all courses using UCLA format.
        $courses = $DB->get_recordset('course', array('format' => 'ucla'));
        foreach ($courses as $course) {
            $course->coursedisplay = COURSE_DISPLAY_MULTIPAGE;
            $DB->update_record('course', $course, true);
        }

        // Ucla savepoint reached.
        upgrade_plugin_savepoint(true, 2012061701, 'format', 'ucla');
    }

    // Map all data from mdl_ucla_course_prefs to mdl_course_format_options.
    if ($oldversion < 2013081203) {
        $table = new xmldb_table('ucla_course_prefs');
        if ($dbman->table_exists($table)) {
            $courseprefs = $DB->get_records('ucla_course_prefs');
            foreach ($courseprefs as $coursepref) {
                unset($coursepref->timestamp);
                $coursepref->format = 'ucla';
                $DB->insert_record('course_format_options', $coursepref, false);
            }
            $dbman->drop_table($table);
        }
        // Ucla savepoint reached.
        upgrade_plugin_savepoint(true, 2013081203, 'format', 'ucla');
    }

    // Fix bug in which "Landing page" was set for "landing_page" for manually
    // created courses (see CCLE-4322).
    if ($oldversion < 2013101600) {
        $sql = "SELECT  *
                FROM    {course_format_options}
                WHERE   format='ucla' AND
                        name='landing_page' AND
                        value LIKE 'Landing page'";
        $records = $DB->get_records_sql($sql);
        if (!empty($records)) {
            foreach ($records as $record) {
                $record->value = 0;
                $DB->update_record('course_format_options', $record, true);
            }
        }

        upgrade_plugin_savepoint(true, 2013101600, 'format', 'ucla');
    }

    // Delete noeditingicons preference for the user.
    // CCLE-4604.
    if ($oldversion < 2014073100) {
        $DB->delete_records('user_preferences', array('name' => 'noeditingicons'));

        upgrade_plugin_savepoint(true, 2014073100, 'format', 'ucla');
    }

    // Update all courses using ucla format to have $course->coursedisplay set
    // to COURSE_DISPLAY_MULTIPAGE.
    if ($oldversion < 2014110500) {
        // Get all courses using UCLA format.
        $courses = $DB->get_recordset('course', array('format' => 'ucla'));
        foreach ($courses as $course) {
            $course->coursedisplay = COURSE_DISPLAY_MULTIPAGE;
            $DB->update_record('course', $course, true);
        }

        // UCLA savepoint reached.
        upgrade_plugin_savepoint(true, 2014110500, 'format', 'ucla');
    }

    // Remove numsections (CCLE-7121), based on MDL-57769.
    if ($oldversion < 2018021300) {
        require_once($CFG->dirroot . '/course/format/ucla/db/upgradelib.php');

        // Remove 'numsections' option. Handle orphaned and to-be-created sections.
        format_ucla_upgrade_remove_numsections();

        upgrade_plugin_savepoint(true, 2018021300, 'format', 'ucla');
    }

    if ($oldversion < 2018050100) {
        // During upgrade to Moodle 3.3 it could happen that general section (section 0) became 'invisible'.
        // It should always be visible.
        $DB->execute("UPDATE {course_sections} SET visible=1 WHERE visible=0 AND section=0 AND course IN
        (SELECT id FROM {course} WHERE format=?)", ['ucla']);
        upgrade_plugin_savepoint(true, 2018050100, 'format', 'ucla');
    }

    if ($oldversion < 2018061500) {
        // Allow only certain roles access to Admin panel. 
        $roles = array('manager', 'editinginstructor', 'supervising_instructor', 
                'ta_instructor', 'ta_admin', 'projectlead', 'projectcontributor', 
                'instructional_assistant', 'grader', 'editor', 
                'studentfacilitator', 'coursesitemanager');
        $context = context_system::instance();
        foreach ($roles as $role) {
            $roleid = $DB->get_field('role', 'id', array('shortname' => $role));
            if (!empty($roleid)) {
                role_change_permission($roleid, $context, 'format/ucla:viewadminpanel', CAP_ALLOW);
            }
        }
        upgrade_plugin_savepoint(true, 2018061500, 'format', 'ucla');
    }
    
    if ($oldversion < 2018062200) {
        // Let only certain roles view support. 
        $permissibleroles = array('manager', 'coursesitemanager');
        $context = context_system::instance();
        $records = $DB->get_records_list('role', 'shortname', $permissibleroles, '', 'id');
        foreach ($records as $record) {
            if (!empty($record->id)) {
                role_change_permission($record->id, $context, 'format/ucla:viewsupport', CAP_ALLOW);
            }
        }
        upgrade_plugin_savepoint(true, 2018062200, 'format', 'ucla');
    }
    
    if ($oldversion < 2018080800) {
        // Add capability to view Admin panel to teaching assistant role. 
        $context = context_system::instance();
        $roleid = $DB->get_field('role', 'id', array('shortname' => 'ta'));
        if (!empty($roleid)) {
            role_change_permission($roleid, $context, 'format/ucla:viewadminpanel', CAP_ALLOW);
        }
        upgrade_plugin_savepoint(true, 2018080800, 'format', 'ucla');
    }

    return true;
}
