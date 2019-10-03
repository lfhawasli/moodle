<?php
// This file is part of the UCLA roles migration plugin for Moodle - http://moodle.org/
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
 * Import script to process all xmls from the uploaded zip file.
 *
 * @package    tool_uclarolesmigration
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$options = array(
    'shortname'     => 1,
    'name'          => 1,
    'description'   => 1,
    'permissions'   => 1,
    'archetype'     => 1,
    'contextlevels' => 1,
    'allowassign'   => 1,
    'allowoverride' => 1,
    'allowswitch'   => 1,
    'allowview'   => 1);

// Variable $importxmls is declared in importroles.php.
foreach ($importxmls as $importxml) {
    $importshortname = get_shortname($importxml);
    if (!isset($actions[$importshortname])) {
        echo html_writer::tag('p', get_string('role_ignored', 'tool_uclarolesmigration', $importshortname));
        continue;
    }

    // There are only three actions available: skip, create, or replace.
    switch ($actions[$importshortname]) {
        case 'skip':
            echo html_writer::tag('p', get_string('role_ignored', 'tool_uclarolesmigration', $importshortname));
            break;
        case 'create':
            if (!array_key_exists($importshortname, $roles['createshortname'])) {
                print_error('new_shortname_undefined');
            }
            $newshortname = core_text::specialtoascii($roles['createshortname'][$importshortname]);
            $newshortname = core_text::strtolower(clean_param($newshortname, PARAM_ALPHANUMEXT));
            $newrolename = $roles['createname'][$importshortname];

            // Code to make new role name/short name if same role name or shortname exists.
            $fullname = $newrolename;
            $shortname = $newshortname;
            $currentfullname = "";
            $currentshortname = "";
            $counter = 0;

            do {
                if ($counter) {
                    $suffixfull = " ".get_string("copyasnoun")." ".$counter;
                    $suffixshort = "_".$counter;
                } else {
                    $suffixfull = "";
                    $suffixshort = "";
                }
                $currentfullname = $fullname.$suffixfull;
                // Limit the size of shortname - database column accepts <= 100 chars.
                $currentshortname = substr($shortname, 0, 100 - strlen($suffixshort)).$suffixshort;
                $coursefull  = $DB->get_record("role", array("name" => $currentfullname));
                $courseshort = $DB->get_record("role", array("shortname" => $currentshortname));
                $counter++;
            } while ($coursefull || $courseshort);

            // Done finding a unique name.
            $definitiontable = new tool_uclarolesmigration_import_table($context, 0);
            $definitiontable->force_preset($importxml, $options);
            $definitiontable->set_name($currentfullname);
            $definitiontable->set_shortname($currentshortname);
            $definitiontable->save_changes();

            // Prep values for string and send to screen.
            $r = new stdClass();
            $r->newshort = $currentshortname;
            $r->newname = $currentfullname;
            $r->newid = $definitiontable->get_roleid();
            $r->oldshort = $shortname;
            $r->oldname = $fullname;
            echo html_writer::tag('p', get_string('new_role_created', 'tool_uclarolesmigration', $r));
            break;

        case 'replace':
            // If the current role is not in the array of incoming roles to replace print error.
            if (!array_key_exists($importshortname, $roles['replace'])) {
                print_error('shortname_to_replace_undefined');
            }

            // Set var with role we're going to update with incoming capabilities.
            $existingrole = $roles['replace'][$importshortname];

            // Grab the DB record for the role we're going to update based on above var just set.
            if (!$rolerecord = $DB->get_record('role', array('shortname' => $existingrole))) {
                print_error('shortname_to_replace_undefined');
            }

            $definitiontable = new tool_uclarolesmigration_import_table($context, $rolerecord->id);
            $definitiontable->force_preset($importxml, $options);
            $definitiontable->save_changes();

            // Prep values for string and send to screen.
            $r = new stdClass();
            $r->new = $existingrole;
            $r->replaced = $importshortname;
            echo html_writer::tag('p', get_string('role_replaced', 'tool_uclarolesmigration', $r));
            break;

        default:
            $a = new stdClass();
            $a->action = $actions[$importshortname];
            $a->shortname = $importshortname;
            echo html_writer::tag('p', get_string('unknown_import_action', 'tool_uclarolesmigration', $a));
    }
}
