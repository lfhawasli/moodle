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

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page.
}

require_once($CFG->libdir.'/formslib.php');

class seniorscholar_import_form extends moodleform {
    public function definition() {
        global $COURSE;

        $mform =& $this->_form;

        if (isset($this->_customdata)) {
            $features = $this->_customdata;
        } else {
            $features = array();
        }

        $mform->addElement('header', 'general', get_string('importfile', 'tool_uclaseniorscholar'));

        // Restrict the possible upload file types.
        if (!empty($features['acceptedtypes'])) {
            $acceptedtypes = $features['acceptedtypes'];
        } else {
            $acceptedtypes = '*';
        }

        if (!empty($features['filter_term'])) {
            $filterterm = $features['filter_term'];
        }

        // File upload.
        $mform->addElement('filepicker', 'userfile', get_string('file'), null, array('accepted_types' => $acceptedtypes));
        $mform->addRule('userfile', null, 'required');
        $encodings = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_uclaseniorscholar'), $encodings);

        if (!empty($features['includeseparator'])) {
            $radio = array();
            $radio[] = $mform->createElement('radio', 'separator', null, get_string('septab', 'tool_uclaseniorscholar'), 'tab');
            $radio[] = $mform->createElement('radio', 'separator', null, get_string('sepcomma', 'tool_uclaseniorscholar'), 'comma');
            $radio[] = $mform->createElement('radio', 'separator', null, get_string('sepcolon', 'tool_uclaseniorscholar'), 'colon');
            $radio[] = $mform->createElement('radio', 'separator', null,
                                             get_string('sepsemicolon', 'tool_uclaseniorscholar'), 'semicolon');
            $mform->addGroup($radio, 'separator', get_string('separator', 'tool_uclaseniorscholar'), ' ', false);
            $mform->setDefault('separator', 'comma');
        }

        $options = array('10' => 10, '20' => 20, '100' => 100, '1000' => 1000, '100000' => 100000);
        $mform->addElement('select', 'previewrows', get_string('rowpreviewnum', 'grades'), $options);
        $mform->setType('previewrows', PARAM_INT);
        $mform->addElement('hidden', 'filter_term', $filterterm);
        $mform->setType('filter_term', PARAM_TEXT);
        $this->add_action_buttons(false, get_string('uploadseniorscholar', 'tool_uclaseniorscholar'));
    }
}