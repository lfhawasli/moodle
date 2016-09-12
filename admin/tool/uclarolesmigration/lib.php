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
 * Import functions library.
 *
 * @package    tool_uclarolesmigration
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * Accepts a role xml file with a role definition and returns the role
 * shortname.
 * 
 * @param string $xml the role's xml definition
 * @return string the role's shortname
 */
function get_shortname($xml) {
    $oldstring = "&nbsp;";
    $newstring = "&#160;";
    $xml = str_replace($oldstring , $newstring, $xml);
    $dom = new DOMDocument();
    $dom->loadXML($xml);
    return get_node_value($dom, '/role/shortname');
}

/**
 * Helper function for get_shortname in order to retrieve
 * a node based off of a path variable.
 * 
 * @param DOMDocument $dom the XML string converted to a DOM document
 * @param string $path the path to get to a specific node
 * @return node
 */
function get_node(DOMDocument $dom, $path) {
    $parts = explode('/', $path);
    $elname = end($parts);

    $nodes = $dom->getElementsByTagName($elname);

    if ($nodes->length == 0) {
        return null;
    }

    foreach ($nodes as $node) {
        if ($node->getNodePath() === $path) {
            return $node;
        }
    }

    return null;
}

/**
 * Helper function for get_shortname in order to retrieve
 * a node's value based off of a path variable.
 * 
 * @param DOMDocument $dom the XML string converted to a DOM document
 * @param string $path the path to get to a specific node
 * @return node value
 */
function get_node_value(DOMDocument $dom, $path) {
    if (!$node = get_node($dom, $path)) {
        return null;
    }
    return $node->nodeValue;
}

/**
 * A function which generates a table for the user to select how to import
 * certain roles.  Options include replacing an existing role with the imported role,
 * creating a new role, and ignoring the imported role.
 * 
 * @param array $incomingrolexmls an array of strings of the role xmls.
 * @param array $actions array of actions to take on the new roles.
 * @return html_table import table
 */
function import_config_table($incomingrolexmls, $actions) {
    global $DB;

    // Existing roles in this installation.
    $existingroles = $DB->get_records('role');

    $incomingroles = array();
    foreach ($incomingrolexmls as $rolexml) {
        $incomingroles[] = tool_uclarolesmigration_cleanxml::parse_preset($rolexml);
    }

    $table = new html_table();
    $table->attributes['class'] = 'generaltable';
    $table->align = array('right', 'left', 'left', 'left');
    $table->wrap = array('nowrap', '', 'nowrap', 'nowrap');
    $table->data = array();
    $table->head = array(get_string('name'), get_string('shortname'),
    get_string('action'));
    if (! is_array($incomingroles)) {
        echo get_string('no_roles_in_import', 'tool_uclarolesmigration');
        return;
    }
    foreach ($incomingroles as $role) {
        $row = array();
        $replacechecked = false;
        $createchecked = false;
        $row[0] = $role['name'];
        $row[1] = $role['shortname'];

        $options = '';

        // Loop through each existing role to set default select field for replace.
        foreach ($existingroles as $er) {
            if ($role['shortname'] == $er->shortname) {
                $selected = ' selected="selected" ';
                $replacechecked = true;
            } else {
                $selected = '';
            }
            $options .= "<option {$selected} value=\"{$er->shortname}\"> {$er->name} ({$er->shortname})</option>";
        }
        if (empty($replacechecked)) {
            $createchecked = true;
        } else {
            $createchecked = false;
        }

        // Generate our table html.
        // Skip radio button (items[0]).
        $items[0] = html_writer::empty_tag('input', array('type' => 'radio', 'id' => 'skip',
            'value' => 'skip', 'name' => 'actions[' . $role['shortname'] . ']'));
        $items[0] .= html_writer::label(get_string('do_not_import', 'tool_uclarolesmigration'), 'skip');

        // Create radio button (items[1]).
        if ($createchecked) {
            $items[1] = html_writer::empty_tag('input', array('type' => 'radio', 'id' => 'create', 'checked' => 'checked',
                'value' => 'create', 'name' => 'actions[' . $role['shortname'] . ']'));
        } else {
            $items[1] = html_writer::empty_tag('input', array('type' => 'radio', 'id' => 'create',
                'value' => 'create', 'name' => 'actions[' . $role['shortname'] . ']'));
        }
        $items[1] .= html_writer::label(get_string('import_new', 'tool_uclarolesmigration'), 'create');

        // Text fields for shortname and name in creating a new role (items[2]).
        $subitems[0] = get_string('shortname', 'tool_uclarolesmigration');
        $subitems[0] .= html_writer::empty_tag('input', array('type' => 'text',
            'name' => 'to_create_shortname[' . $role['shortname'] . ']', 'value' => $role['shortname']));
        $subitems[1] = get_string('name', 'tool_uclarolesmigration');
        $subitems[1] .= html_writer::empty_tag('input', array('type' => 'text',
            'name' => 'to_create_name[' . $role['shortname'] . ']', 'value' => $role['name']));
        $items[2] = html_writer::alist($subitems, array('style' => 'list-style-type: none;margin:0 0 0 35px;padding:0;'), 'ul');

        // Replace radio button (items[3]).
        if ($replacechecked) {
            $items[3] = html_writer::empty_tag('input', array('type' => 'radio', 'id' => 'replace', 'checked' => 'checked',
                'value' => 'replace', 'name' => 'actions[' . $role['shortname'] . ']'));
        } else {
            $items[3] = html_writer::empty_tag('input', array('type' => 'radio', 'id' => 'replace',
                'value' => 'replace', 'name' => 'actions[' . $role['shortname'] . ']'));
        }
        $items[3] .= html_writer::label(get_string('import_replacing', 'tool_uclarolesmigration'), 'replace');
        // Kept raw for simplicity, this selects an existing role with the same shortname as the imported role.
        $items[3] .= '<select name="to_replace[' . $role['shortname'] . ']" >' . $options . '</select>';

        // Finally, store all of the above html information as our third column, named row[2].
        $row[2] = html_writer::alist($items, array('style' => 'list-style-type: none;'), 'ul');

        $table->data[] = $row;
    }

    return $table;
}