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
 * Rearrange form
 *
 * @package block_ucla_rearrange
 * @copyright UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

/**
 * Web Form for rearrange
 *
 * @package block_ucla_rearrange
 * @copyright UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ucla_rearrange_form extends moodleform {

    /**
     * Define elements in the form
     * @return void
     */
    public function definition() {
        $mform =& $this->_form;

        $courseid     = $this->_customdata['courseid'];
        $section      = $this->_customdata['section'];
        $sectionshtml = $this->_customdata['sectionshtml'];

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'section', $section);
        $mform->setType('section', PARAM_INT);

        $mform->addElement('hidden', 'serialized', '',
                array('id' => 'serialized'));
        $mform->setType('serialized', PARAM_RAW);

        // CCLE-3930 - Rearrange erases modules in sections when javascript is turned off or not fully loaded.
        // First set of submit and cancel buttons. Initially disabled.
        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton1',
                get_string('savechanges'),
                array('class' => 'btn-submit', 'disabled' => 'disabled'));
        $buttonarray[] = $mform->createElement('cancel', 'cancelbutton1');
        $mform->addGroup($buttonarray, 'buttonar1', '', ' ', false);

        $mform->addElement('header', 'header', get_string('sections'));

        // Initially all sections are expanded, so the button should be collapse all.
        $collapseall = get_string('allcollapse', 'block_ucla_rearrange');
        $classset1 = array('class' => 'expandall', 'id' => 'mass-expander-top',
                           'type' => 'button', 'value' => $collapseall);

        $mform->addElement('html', html_writer::tag('input', '', $classset1));

        $jsnotice = html_writer::tag('div',
            get_string('javascriptrequired', 'group'),
            array('class' => 'js-hide'));
        $mform->addElement('html', html_writer::tag('div',
            $jsnotice . $sectionshtml,
            array('id' => block_ucla_rearrange::PRIMARY_DOMNODE)));

        // CCLE-3930 - Rearrange erases modules in sections when javascript is turned off or not fully loaded.
        // Second set of submit and cancel buttons. Initially disabled.
        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton2',
                get_string('savechanges'),
                array('class' => 'btn-submit', 'disabled' => 'disabled'));
        $buttonarray[] = $mform->createElement('cancel', 'cancelbutton2');
        $mform->addGroup($buttonarray, 'buttonar2', '', ' ', false);
    }

    /**
     * If user hits cancel, rather than reset the form, redirect them to the
     * section that they were previously on.
     */
    public function is_cancelled() {
        $result = parent::is_cancelled();

        if (!empty($result)) {
            $courseid = $this->_customdata['courseid'];
            $section  = $this->_customdata['section'];

            redirect(new moodle_url('/course/view.php',
                array('id' => $courseid, 'section' => $section)));
        }
    }
}
