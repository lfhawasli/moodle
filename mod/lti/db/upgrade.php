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
//
// This file is part of BasicLTI4Moodle
//
// BasicLTI4Moodle is an IMS BasicLTI (Basic Learning Tools for Interoperability)
// consumer for Moodle 1.9 and Moodle 2.0. BasicLTI is a IMS Standard that allows web
// based learning tools to be easily integrated in LMS as native ones. The IMS BasicLTI
// specification is part of the IMS standard Common Cartridge 1.1 Sakai and other main LMS
// are already supporting or going to support BasicLTI. This project Implements the consumer
// for Moodle. Moodle is a Free Open source Learning Management System by Martin Dougiamas.
// BasicLTI4Moodle is a project iniciated and leaded by Ludo(Marc Alier) and Jordi Piguillem
// at the GESSI research group at UPC.
// SimpleLTI consumer for Moodle is an implementation of the early specification of LTI
// by Charles Severance (Dr Chuck) htp://dr-chuck.com , developed by Jordi Piguillem in a
// Google Summer of Code 2008 project co-mentored by Charles Severance and Marc Alier.
//
// BasicLTI4Moodle is copyright 2009 by Marc Alier Forment, Jordi Piguillem and Nikolas Galanis
// of the Universitat Politecnica de Catalunya http://www.upc.edu
// Contact info: Marc Alier Forment granludo @ gmail.com or marc.alier @ upc.edu.

/**
 * This file keeps track of upgrades to the lti module
 *
 * @package mod_lti
 * @copyright  2009 Marc Alier, Jordi Piguillem, Nikolas Galanis
 *  marc.alier@upc.edu
 * @copyright  2009 Universitat Politecnica de Catalunya http://www.upc.edu
 * @author     Marc Alier
 * @author     Jordi Piguillem
 * @author     Nikolas Galanis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 defined('MOODLE_INTERNAL') || die;

/**
 * xmldb_lti_upgrade is the function that upgrades
 * the lti module database when is needed
 *
 * This function is automaticly called when version number in
 * version.php changes.
 *
 * @param int $oldversion New old version number.
 *
 * @return boolean
 */
function xmldb_lti_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2016052301) {

        // Changing type of field value on table lti_types_config to text.
        $table = new xmldb_table('lti_types_config');
        $field = new xmldb_field('value', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'name');

        // Launch change of type for field value.
        $dbman->change_field_type($table, $field);

        // Lti savepoint reached.
        upgrade_mod_savepoint(true, 2016052301, 'lti');
    }

    // Automatically generated Moodle v3.2.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.3.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.4.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2017111301) {

        // A bug in the LTI plugin incorrectly inserted a grade item for
        // LTI instances which were set to not allow grading.
        // The change finds any LTI which does not have grading enabled,
        // and updates any grades to delete them.

        $ltis = $DB->get_recordset_sql("
                SELECT
                       l.id,
                       l.course,
                       l.instructorchoiceacceptgrades,
                       t.enabledcapability,
                       t.toolproxyid,
                       tc.value AS acceptgrades
                  FROM {lti} l
            INNER JOIN {grade_items} gt
                    ON l.id = gt.iteminstance
             LEFT JOIN {lti_types} t
                    ON t.id = l.typeid
             LEFT JOIN {lti_types_config} tc
                    ON tc.typeid = t.id AND tc.name = 'acceptgrades'
                 WHERE gt.itemmodule = 'lti'
                   AND gt.itemtype = 'mod'
        ");

        foreach ($ltis as $lti) {
            $acceptgrades = true;
            if (empty($lti->toolproxyid)) {
                $typeacceptgrades = isset($lti->acceptgrades) ? $lti->acceptgrades : 2;
                if (!($typeacceptgrades == 1 ||
                        ($typeacceptgrades == 2 && $lti->instructorchoiceacceptgrades == 1))) {
                    $acceptgrades = false;
                }
            } else {
                $enabledcapabilities = explode("\n", $lti->enabledcapability);
                $acceptgrades = in_array('Result.autocreate', $enabledcapabilities);
            }

            if (!$acceptgrades) {
                // Required when doing CLI upgrade.
                require_once($CFG->libdir . '/gradelib.php');
                grade_update('mod/lti', $lti->course, 'mod', 'lti', $lti->id, 0, null, array('deleted' => 1));
            }

        }

        $ltis->close();

        upgrade_mod_savepoint(true, 2017111301, 'lti');
    }

    // START UCLA MOD: CCLE-6956 - LTI Apps in Rich Text Editor.
    if ($oldversion < 2017111302) {
        $table = new xmldb_table('lti_types');
        
        $fields = [];
        $fields[] = new xmldb_field('asactivity', XMLDB_TYPE_INTEGER, 1, null, null, null, 1, 'secureicon');
        $fields[] = new xmldb_field('asassignment', XMLDB_TYPE_INTEGER, 1, null, null, null, 0, 'asactivity');
        $fields[] = new xmldb_field('assignmenturl', XMLDB_TYPE_TEXT, 255, null, false, null, null, 'asassignment');
        $fields[] = new xmldb_field('asmenulink', XMLDB_TYPE_INTEGER, 1, null, null, null, 0, 'assignmenturl');
        $fields[] = new xmldb_field('menulinkurl', XMLDB_TYPE_TEXT, 255, null, false, null, null, 'asmenulink');
        $fields[] = new xmldb_field('asrichtexteditorplugin', XMLDB_TYPE_INTEGER, 1, null, null, null, 0, 'menulinkurl');
        $fields[] = new xmldb_field('richtexteditorurl', XMLDB_TYPE_TEXT, 255, null, false, null, null, 'asrichtexteditorplugin');
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        
        upgrade_mod_savepoint(true, 2017111302, 'lti');
    }
    // END UCLA MOD: CCLE-6956.

    // Automatically generated Moodle v3.5.0 release upgrade line.
    // Put any upgrade step following this.

    // START UCLA MOD: CCLE-6955 - LTI Apps in Course Menu Links.
    if ($oldversion < 2018051401) {
        $placementstablename = 'lti_course_menu_placements';
        $table = new xmldb_table($placementstablename);

        $table->add_field('id', XMLDB_TYPE_INTEGER, 11, true, true, XMLDB_SEQUENCE, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, 11, true, true, false, null, 'id');
        $table->add_field('typeid', XMLDB_TYPE_INTEGER, 11, true, true, false, null, 'course');
        
        // Adding keys to table lti_course_menu_placements.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        
        if (!$dbman->table_exists($placementstablename)) {
            $dbman->create_table($table);
        }
        
        upgrade_mod_savepoint(true, 2018051401, 'lti');
    }
    // END UCLA MOD: CCLE-6955.

    return true;

}
