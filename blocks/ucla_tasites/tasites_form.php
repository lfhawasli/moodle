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

    private function define_admin_form() {
        $mform =& $this->_form;
        
        // User is not a TA, but is an instructor or admin who can make TA
        // sites for other TAs.
        $course = $this->_customdata['course'];
        $mapping = $this->_customdata['mapping'];
        $enablebysection = get_config('block_ucla_tasites', 'enablebysection');
        
        /*
         * Do not display initial screen in the following scenario:
         *  When there is no TAs or sections available for the course 
         *  When TA sites are created for each TA/each Section accordingly.
         * Do not show By TA option
         *  When there is no atleast one TA available for the course OR
         *  Wben all the TAs have their own TA site.
         * Do not show By Section option
         *  When there is no atleast one section available for the course OR
         *  When all the sections have their own TA sites created.
         */

        // Display initial screen for instructors if we are allowing
        // TA site creation by section.
        if ($enablebysection) {
            $mform->addElement('static', 'tainitialdesc', '',
            get_string('tainitialdesc', 'block_ucla_tasites'));
            
            $choicearray = array();
            $choicearray[] = $mform->createElement('radio', 'tainitialchoice', '',
                    get_string('tainitialbyta', 'block_ucla_tasites'), 'byta');
            $choicearray[] = $mform->createElement('radio', 'tainitialchoice', '',
                    get_string('tainitialbysection', 'block_ucla_tasites'), 'bysection');
            $mform->addGroup($choicearray, 'tainitialchoicegroup', '', '<br />', false);
            $mform->addRule('tainitialchoicegroup', null, 'required');

            $mform->addElement('header','bytaheader', get_string('bytaheader', 'block_ucla_tasites'));
            $mform->setExpanded('bytaheader');
        }
        
        $mform->addElement('static', 'bytadesc', '',
            get_string('bytadesc', 'block_ucla_tasites'));
        
        $tachoicearray = array();
        $sections = array();
        foreach ($mapping['byta'] as $fullname => $tainfo) {
            if (block_ucla_tasites::has_tasite($course->id, $tainfo['ucla_id'])) {
                continue;
            }

            if(isset($tainfo['secsrs'])) {
                $sections = array_keys($tainfo['secsrs']);
                $sections = array_map(array('block_ucla_tasites', 'format_sec_num'), $sections);
            }
            $a = new stdClass();
            $a->fullname = $fullname;
            $a->sections = !empty($sections) ? implode(', ', $sections) : '0';
            $tachoicearray[] = $mform->createElement('radio', 'byta', '',
                    get_string('bytachoice', 'block_ucla_tasites', $a), $tainfo['ucla_id']);
        }
        if (!empty($tachoicearray)) {
            // If there are no TAs with no TA sites, then don't list them.
            $mform->addGroup($tachoicearray, 'bytachoicegroup', '', '<br />', false);
            //$mform->addRule('bytachoicegroup', null, 'required');
        }

        $mform->addElement('checkbox', 'tasectionchoiceentire', '',
                get_string('bytaentirecourse', 'block_ucla_tasites'));
        
        $mform->addElement('header','bysectionheader', get_string('bysectionheader', 'block_ucla_tasites'));
        $mform->addElement('static', 'bysectiondesc', '',
            get_string('bysectiondesc', 'block_ucla_tasites'));
        $mform->setExpanded('bysectionheader');

        foreach($mapping['bysection'] as $secnum => $secinfo) {
            if(isset($secinfo['tas'])) {
                $canmaketasite = false;
                foreach($secinfo['tas'] as $ucla_id => $taname) {
                    if (!block_ucla_tasites::has_tasite($course->id, $ucla_id)) {
                        $canmaketasite = true;
                        break;
                    }
                }
                if(!$canmaketasite) {
                    continue;
                }

                $a = new stdClass();
                $a->sec = block_ucla_tasites::format_sec_num($secnum);
                $a->tas = implode(',', $secinfo['tas']);
                $mform->addElement('checkbox', 'bysection['.$secnum.']', '',  get_string('bysectionchoice', 'block_ucla_tasites', $a));
            }
        }

        $this->define_agreement_form();
    }

    private function define_agreement_form() {
        $mform =& $this->_form;

        // Policy agreement statement.
        $mform->addElement('header', 'confirmationheader', '');
        $mform->addElement('checkbox', 'confirmation', '',
                get_string('tasitecreateconfirm', 'block_ucla_tasites'));
        $mform->addRule('confirmation',
                get_string('errconfirmation', 'block_ucla_tasites'),
                'required', null, 'server');
    }

    private function define_ta_form() {
        global $USER;

        $mform =& $this->_form;
        $mapping = $this->_customdata['mapping'];

        $fullname = fullname($USER);
        // Looking for a section that belongs to current user.
        if (isset($mapping['byta'][$fullname]['secsrs'])) {
            $mform->addElement('static', 'tasectiondesc', '',
                get_string('tasectiondesc', 'block_ucla_tasites'));

            $sections = array_keys($mapping['byta'][$fullname]['secsrs']);
            $sections = array_map(array('block_ucla_tasites', 'format_sec_num'), $sections);

            $choicearray = array();
            $choicearray[] = $mform->createElement('radio', 'tasectionchoice', '',
                    get_string('tasectionchoiceonly', 'block_ucla_tasites',
                            implode(', ', $sections)), 'only');
            $choicearray[] = $mform->createElement('radio', 'tasectionchoice', '',
                    get_string('tasectionchoiceentire', 'block_ucla_tasites'), 'all');
            $mform->addGroup($choicearray, 'tasectionchoicegroup', '', '<br />', false);
            $mform->addRule('tasectionchoicegroup', null, 'required');
        }

        $mform->addElement('hidden', 'byta', $USER->idnumber);
        $mform->setType('byta', PARAM_INT);

        $mform->addElement('hidden', 'screen', 'byta');
        $mform->setType('screen', PARAM_ALPHA);

        $this->define_agreement_form();
    }

    /**
     * Definition.
     */
    public function definition() {
        global $USER;
        $validationerror = null;

        $mform =& $this->_form;
        $mapping = $this->_customdata['mapping'];

        if (empty($mapping)) {
            $validationerror = get_string('notaorsection', 'block_ucla_tasites');
        } else if (empty($mapping['byta'])) {
            $validationerror = get_string('notasites', 'block_ucla_tasites');
        } else if (empty($mapping['bysection']) && block_ucla_tasites::create_tasite_bysection_allowed($course->id)) {
            $validationerror = get_string('nosectionsexist', 'block_ucla_tasites');
        }

        $mform->addElement('hidden', 'tasiteaction', 'create');
        $mform->setType('tasiteaction', PARAM_ALPHA);

        $course = $this->_customdata['course'];
        $mform->addElement('hidden', 'courseid', $course->id);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('header','createheader', get_string('create', 'block_ucla_tasites'));
        
        // If the user is a TA, display TA form.
        if (block_ucla_tasites::can_have_tasite($USER, $course->id)) {
            $this->define_ta_form();
        } else {
            $this->define_admin_form();
        }
        
        $this->validation($mapping);
        $this->add_action_buttons(false, get_string('create', 'block_ucla_tasites'));
    }

    /**
     * Verifies that the form is ready to be processed.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files=null) {
        $errors = array();
//        $course = $this->_customdata['course'];
//
//        //print_object($data);
//        if (block_ucla_tasites::create_tasite_bysection_allowed($course->id) && !empty($data['byta'])) {
//            return;
//        } else {
//           // $errors['notasitebysection'] = get_string('notasitebysection', 'block_ucla_tasites');
//        }

//        // Check if at least one section is choosen for bysection and it is valid.
//        $mapping = block_ucla_tasites::get_tasection_mapping($data['courseid']);
//        $validsectionfound = false;
//        foreach ($data['bysection'] as $section => $value) {
//            if (!empty($value)) {
//                // Section choosen, now make sure it exists.
//                if (isset($mapping['bysection'][$section])) {
//                    $validsectionfound = true;
//                }
//            }
//        }
//
//        if (!$validsectionfound) {
//            $retval['sectionheader'] = get_string('errinvalidsetupselected', 'block_ucla_tasites');
//        }

       // return $retval;
        return $errors;

    }
}
