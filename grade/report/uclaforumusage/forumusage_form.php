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
 * Forum form layout.
 *
 * @package     gradereport_uclaforumusage
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class forumusage_form extends moodleform {
    public function definition() {
        global $CFG, $DB;

        $mform = $this->_form;
        $user = $this->_customdata['user'];
        $studentlist = $this->_customdata['studentlist'];
        $forumlist = $this->_customdata['forumlist'];
        $forums = $this->_customdata['forums'];
        $forumtype = $this->_customdata['forumtype'];

        if (is_array($forums)) {
            $forums = array_flip($forums);
        }
        $courseid = $this->_customdata['courseid'];
        $mform->addElement('select', 'student', get_string('studentlist', 'gradereport_uclaforumusage'), $studentlist);
        $mform->setDefault('student', 0);
        foreach ($forumlist as $id => $value) {
            $mform->addElement('advcheckbox', 'forum['.$id.']', $value, null, null, $id);
            // Default: all set.
            if (empty($forums)) {
                $mform->setDefault('forum['.$id.']', true);
            } else {
                $mform->setDefault('forum['.$id.']', false);
            }
        }
        // Courseid.
        $mform->addElement('advcheckbox', 'forumtype', null, get_string('displayforum', 'gradereport_uclaforumusage'), null, $forumtype);
        $mform->setDefault('forumtype', true);
        $mform->addElement('hidden', 'id', $courseid);
        $mform->setType('id', PARAM_INT);
        $this->add_action_buttons(false, 'Go');
        $this->set_data(array('action' => 'formsubmitted', 'user' => $user->id));
    }
}

