<?php
// This file is part of the UCLA support tools plugin for Moodle - http://moodle.org/
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
 * Import roles form.
 *
 * @package    tool_uclarolesmigration
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

/**
 * Accepts a zip file to import support tools.
 *
 * @package    local_ucla_support_tools
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_form extends moodleform {

    /**
     * Parsed data from the import file.
     * @var array
     */
    var $importdata = array();

    /**
     * Displays file picker for user to upload file.
     */
    protected function definition() {
        $mform =& $this->_form;
        $mform->addElement('filepicker', 'importfile', get_string('files'), null, array('accepted_types' => 'txt'));
        $mform->addRule('importfile', get_string('required'), 'required', null, 'client');
        $this->add_action_buttons(false, get_string('importfile', 'backup'));
    }
}
