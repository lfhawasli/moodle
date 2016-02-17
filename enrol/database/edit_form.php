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
 * Allow customize email message when student enrol in a course through registrar.
 *
 * @package    enrol_database
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Edit form implementation.
 *
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_database_edit_form extends moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        global $COURSE, $DB;

        $mform = $this->_form;

        list($instance, $plugin, $context) = $this->_customdata;

        $mform->addElement('header', 'header', get_string('pluginname', 'enrol_database'));

        $mform->addElement('advcheckbox', 'customint4',
                get_string('sendcoursewelcomemessage', 'local_ucla'));
        $mform->addHelpButton('customint4', 'sendcoursewelcomemessage', 'local_ucla');

        $mform->addElement('text', 'customchar1',
                get_string('customwelcomemessagefrom', 'local_ucla'), array('size' => '60'));
        $mform->setType('customchar1', PARAM_EMAIL);
        $mform->addHelpButton('customchar1', 'customwelcomemessagefrom', 'local_ucla');

        $mform->addElement('text', 'customchar2',
                get_string('customwelcomemessagesubject', 'local_ucla'), array('size' => '60'));
        $mform->setType('customchar2', PARAM_TEXT);
        $mform->setDefault('customchar2', get_string('welcometocourse', 'local_ucla', $COURSE->fullname));

        $mform->addElement('textarea', 'customtext1',
                get_string('customwelcomemessage', 'local_ucla'), array('cols' => '60', 'rows' => '8'));
        $mform->setDefault('customtext1', get_string('welcometocoursetext', 'local_ucla'));
        $mform->addHelpButton('customtext1', 'customwelcomemessage', 'local_ucla');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons(true, ($instance->id ? null : get_string('addinstance', 'enrol')));

        $this->set_data($instance);
    }

}
