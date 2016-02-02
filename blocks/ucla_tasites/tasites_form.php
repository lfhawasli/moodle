<?php
// This file is part of the UCLA TA sites block for Moodle - http://moodle.org/
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
 * TA site form.
 *
 * @package    block_ucla_tasites
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

/**
 * Form class.
 *
 * @package    block_ucla_tasites
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tasites_form extends moodleform {

    /**
     * Definition.
     */
    public function definition() {
        $mform =& $this->_form;

        $action = $this->_customdata['action'];
        $mform->addElement('hidden', 'action', $action);
        $mform->setType('action', PARAM_ALPHA);

        $courseid = $this->_customdata['courseid'];
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

//        $tasiteinfo = $this->_customdata['tasiteinfo'];

        // Get mapping of sections and TAs.
        $mapping = block_ucla_tasites::get_tasection_mapping($courseid);

        $mform->addElement('header', 'bysectionheader', 
                get_string('bysectionheader', 'block_ucla_tasites'));
        $mform->addElement('static', 'bysection',
                get_string('bysection', 'block_ucla_tasites'),
                get_string('bysectiondesc', 'block_ucla_tasites'));

        // Add options to create TA site by section.
        $bysectionbox = array();
        foreach ($mapping['bysection'] as $secnum => $sectioninfo) {
            $a = new stdClass();
            if ($secnum == 'all') {
                // Course doesn't have sections.
                $label = get_string('bysectionlabelall', 'block_ucla_tasites');
            } else {
                $a->sec = block_ucla_tasites::format_sec_num($secnum);
                $label = get_string('bysectionlabel', 'block_ucla_tasites', $a);
            }
            if (!empty($sectioninfo['tas'])) {
                $a->tas = implode(' / ', $sectioninfo['tas']);
            } else {
                $a->tas = 'N/A';
            }
            $text = get_string('bysectiontext', 'block_ucla_tasites', $a);
            $mform->addElement('advcheckbox', 'bysection['.$secnum.']', $label,
                    $text, array('group' => 1));
        }
        $this->add_checkbox_controller(1);

        // Policy agreement statement.
        $mform->addElement('checkbox', 'confirmation', '',
                get_string('tasitecreateconfirm', 'block_ucla_tasites'));
        $mform->addRule('confirmation',
                get_string('errconfirmation', 'block_ucla_tasites'),
                'required', null, 'server');

        // Add advanced features, such as ability to edit shortname and title.
        $mform->addElement('header', 'miscellaneoussettingshdr',
                get_string('miscellaneoussettings', 'form'));
        $mform->setAdvanced('miscellaneoussettingshdr');
        $mform->addElement('static', 'coursename', '',
                get_string('coursenamedesc', 'block_ucla_tasites'));
        $mform->addElement('text', 'shortname', get_string('shortnamecourse'));
        $mform->setType('shortname', PARAM_TEXT);
        $mform->addElement('text', 'fullname', get_string('fullnamecourse'));
        $mform->setType('fullname', PARAM_TEXT);


        $this->add_action_buttons(true, get_string('create', 'block_ucla_tasites'));
    }

    /**
     * Verifies that the form is ready to be processed.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $retval = array();

        // Check if at least one section is choosen for bysection and it is valid.
        $mapping = block_ucla_tasites::get_tasection_mapping($data['courseid']);
        $validsectionfound = false;
        foreach ($data['bysection'] as $section => $value) {
            if (!empty($value)) {
                // Section choosen, now make sure it exists.
                if (isset($mapping['bysection'][$section])) {
                    $validsectionfound = true;
                }
            }
        }

        if (!$validsectionfound) {
            $retval['sectionheader'] = get_string('errinvalidsetupselected', 'block_ucla_tasites');
        }

        return $retval;
    }
}
