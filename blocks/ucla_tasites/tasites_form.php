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

        $courseid = $this->_customdata['courseid'];
        $tasiteinfo = $this->_customdata['tasiteinfo'];

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $formactions = array(
            'view' => array(),
            'build' => array()
        );

        foreach ($tasiteinfo as $tainfo) {
            if (empty($tainfo->tasite)) {
                $formaction = 'build';
            } else {
                $formaction = 'view';
            }

            $formactions[$formaction][] = $tainfo;
        }

        foreach ($formactions as $action => $tas) {
            if (empty($tas)) {
                continue;
            }

            $mform->addElement('header', $action . '_header',
                get_string($action . '_tasites', 'block_ucla_tasites'));

            if ($action == 'view') {
                $mform->addElement('html', html_writer::start_tag('ul'));
            }

            foreach ($tas as $ta) {
                // View is special.
                if ($action == 'view') {
                    $talink = html_writer::link(
                            $ta->courseurl,
                            get_string(
                                $action . '_tadesc', 'block_ucla_tasites',
                                $ta
                            ));
                    $tagrouping = get_string('view_tadesc_grouping',
                            'block_ucla_tasites',
                            $ta->tasite->defaultgroupingname);

                    $mform->addElement('html',
                        html_writer::tag('li', $talink . $tagrouping));
                } else {
                    // This specifies whether to take the action or not.
                    $mform->addElement(
                        'checkbox',
                        block_ucla_tasites::checkbox_naming($ta),
                        '',
                        get_string($action . '_tadesc', 'block_ucla_tasites',
                            $ta)
                    );
                }

                // This specifies what action to take for the TA.
                $mform->addElement(
                    'hidden',
                    block_ucla_tasites::action_naming($ta),
                    $action
                );
                $mform->setType(block_ucla_tasites::action_naming($ta), PARAM_ACTION);
            }

            if ($action == 'view') {
                $mform->addElement('html', html_writer::end_tag('ul'));
            }
        }

        $this->add_action_buttons();
    }
}
