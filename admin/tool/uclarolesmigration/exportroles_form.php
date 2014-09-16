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
 * Export roles moodleform.
 *
 * @package    tool_uclarolesmigration
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Export roles moodleform class.
 *
 * @package    tool_uclarolesmigration
 * @category   form
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class export_roles_form extends moodleform {

    /**
     * The form definition.
     */
    protected function definition() {
        $mform =& $this->_form;
        $mform->setType('contextid', PARAM_INT);
        $mform->setType('export', PARAM_RAW);

        $contextid = $this->_customdata['contextid'];

        // Will be overwritten below.
        $export = $mform->addElement('hidden', 'export', '');

        $table = new html_table();
        // Styling done using HTML table and CSS.
        $table->attributes['class'] = 'generaltable';
        $table->align = array('left', 'left', 'left', 'center');
        $table->wrap = array('nowrap', '', 'nowrap', 'nowrap');
        $table->data = array();
        $table->head = array(
            get_string('name'),
            get_string('description'),
            get_string('shortname'),
            get_string('export', 'tool_uclarolesmigration')
        );

        $roles = get_all_roles();
        $roleids = array();
        foreach ($roles as $role) {
            $row = array();
            $roleurl = new moodle_url('/admin/roles/define.php', array('roleid' => $role->id, 'action' => 'view'));
            $row[0] = html_writer::link($roleurl, format_string($role->name));
            $row[1] = format_text($role->description, FORMAT_HTML);
            $row[2] = $role->shortname;
            // Export values are added from role checkboxes.
            $row[3] = html_writer::checkbox('export[]', $role->shortname, false);
            $table->data[] = $row;
        }

        $mform->addElement('html', html_writer::table($table));
        $mform->addElement('hidden', 'contextid', $contextid);

        $this->add_action_buttons(false, get_string('submitexport', 'tool_uclarolesmigration'));
    }

    /**
     * Validates the form, ensuring that at least one
     * role is selected to export.
     * 
     * @param array $data submitted data
     * @param array $files not used
     * @return array $errors
     */
    public function validation($data, $files) {
        $errors = array();
        if (empty($data['export'])) {
            $errors['export'] = get_string('error_noselect', 'tool_uclarolesmigration');
        }
        return $errors;
    }
}
