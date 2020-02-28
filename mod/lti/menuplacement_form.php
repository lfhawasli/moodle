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
 * This page allows instructors to configure course level tool providers.
 *
 * @package    mod_lti
 * @copyright  2020 The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     David Shepard, Lillian Hawasli
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

/**
 * Tool selection form.
 *
 * @package    mod_lti
 * @copyright  2020 The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     David Shepard
 */
class mod_lti_menuplacement_form extends moodleform {

    /**
     * Creates form to display tools available.
     */
    protected function definition() {
        $mform = $this->_form;

        $selectedtypesforcourse = $this->_customdata['menulinks'];
        foreach ($selectedtypesforcourse as $type) {
            $checkboxid = 'ltitool-' . $type->id;
            $labeltext = html_writer::tag('strong', $type->name);
            if (!empty($type->description)) {
                $labeltext .= '<br />' . $type->description;
            }
            $mform->addElement('advcheckbox', $checkboxid, $labeltext, NULL, NULL, [NULL, $type->id]);
            $mform->setDefault($checkboxid, $type->selected);
        }

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->setConstant('courseid', $this->_customdata['courseid']);

        $this->add_action_buttons();
    }
}