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
 * Form class for editing modchooser preferences.
 *
 * @package     core_course
 * @copyright   2016 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->libdir . '/formslib.php');

/**
 * Modchooser preferences form.
 *
 * @package core_course
 * @copyright 2016 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_modchooser_preferences_form extends moodleform {

    /**
     * The form definition.
     */
    public function definition() {
        global $USER, $CFG;

        $mform = $this->_form;

        $returnto = $this->_customdata['returnto'];

        $mform->addElement('header', 'modchooser', get_string('modchoosersetting'));
        $mform->addElement('advcheckbox', 'modchoosersetting', '', get_string('modchoosersetting_str'));
        $mform->setType('modchoosersetting', PARAM_INT);
        $mform->setDefault('modchoosersetting', 0);
        $mform->addHelpButton('modchooser', 'modchoosersetting');

        $mform->addElement('hidden', 'returnto', null);
        $mform->setType('returnto', PARAM_ALPHANUM);
        $mform->setConstant('returnto', $returnto);

        $this->add_action_buttons();
    }
}
