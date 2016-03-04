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
     * Defines the admin form elements.
     */
    private function define_admin_form() {
        $mform =& $this->_form;
        
        // User is not a TA, but is an instructor or admin who can make TA
        // sites for other TAs.
        $course = $this->_customdata['course'];
        $mapping = $this->_customdata['mapping'];
        $hassections = empty($mapping['bysection']['all']);

        // We allow TA site creation by sections if enabled at site level and
        // course has sections.
        $enablebysection = get_config('block_ucla_tasites', 'enablebysection') &&
                $hassections;

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
        if ($enablebysection && isset($mapping['byta'])) {
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

        if (isset($mapping['byta'])) {
            $this->define_ta_section();

            // If there are no TAs, then don't need to show section header,
            // because there wouldn't be an option to choose between creating a
            // TA site based by TA or section.
            $mform->addElement('header','bysectionheader', get_string('bysectionheader', 'block_ucla_tasites'));
            $mform->setExpanded('bysectionheader');
        }

        if ($hassections && $enablebysection) {
            $mform->addElement('static', 'bysectiondesc', '',
                get_string('bysectiondesc', 'block_ucla_tasites'));

            foreach ($mapping['bysection'] as $secnum => $secinfo) {
                $canmaketasite = false;
                foreach ($secinfo['secsrs'] as $secsrs) {
                    if (!block_ucla_tasites::has_sec_tasite($course->id, $secsrs)) {
                        $canmaketasite = true;
                        break;
                    }
                }
                if (!$canmaketasite) {
                    continue;
                }

                $a = new stdClass();
                $a->sec = block_ucla_tasites::format_sec_num($secnum);
                $a->tas = implode(',', $secinfo['tas']);
                if (!empty($a->tas)) {
                    $a->tas = '(TAs - ' . $a->tas . ')';
                }
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

    /**
     * Displays the form for individual TAs.
     */
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
     * Displays the creation of TA sites by TA for admins.
     */
    public function define_ta_section() {
        $mform =& $this->_form;
        $course = $this->_customdata['course'];
        $mapping = $this->_customdata['mapping'];
        $hassections = empty($mapping['bysection']['all']);

        $tachoicearray = array();
        $sections = array();

        $availbletas = false;   // Do we have any TAs without TA sites?
        foreach ($mapping['byta'] as $fullname => $tainfo) {
            if (block_ucla_tasites::has_tasite($course->id, $tainfo['ucla_id'])) {
                continue;
            }
            $availbletas = true;

            if (isset($tainfo['secsrs'])) {
                $sections = array_keys($tainfo['secsrs']);
                $sections = array_map(array('block_ucla_tasites', 'format_sec_num'), $sections);
            }

            if ($hassections) {
                $a = new stdClass();
                $a->fullname = $fullname;
                $a->sections = implode(', ', $sections);
                $tachoice = get_string('bytachoice', 'block_ucla_tasites', $a);
            } else {
                $tachoice = $fullname;
            }
            $tachoicearray[] = $mform->createElement('radio', 'byta', '',
                    $tachoice, $tainfo['ucla_id']);
        }

        // If no TAs were avaialble, let's display a message and thing else.
        if (!$availbletas) {
            $mform->addElement('static', 'bytadesc', '',
                    get_string('unavaibletas', 'block_ucla_tasites'));
            return;
        }

        $mform->addElement('static', 'bytadesc', '',
            get_string('bytadesc', 'block_ucla_tasites'));

        if (!empty($tachoicearray)) {
            // If there are no TAs with no TA sites, then don't list them.
            $mform->addGroup($tachoicearray, 'bytachoicegroup', '', '<br />', false);
        }

        if ($hassections) {
            $mform->addElement('checkbox', 'tasectionchoiceentire', '',
                    get_string('bytaentirecourse', 'block_ucla_tasites'));
        } else {
            $mform->addElement('static', 'bytanosectionsnote', '',
                    get_string('bytanosectionsnote', 'block_ucla_tasites'));
        }
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

        $this->add_action_buttons(false, get_string('create', 'block_ucla_tasites'));
        $mform->disable_form_change_checker();
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
