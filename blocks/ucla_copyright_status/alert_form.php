<?php
// This file is part of UCLA copyright status block for Moodle - http://moodle.org/
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
 * UCLA copyright status alert form definition.
 *
 * @package    block
 * @subpackage ucla_copyright_status
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

class copyright_alert_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('html', html_writer::tag('div',
                get_string('alert_msg', 'block_ucla_copyright_status', $this->_customdata)));

        // Need to use multiple submit buttons so that form is sent without the...
        // ...need for js onclick handlers.
        $alertbuttons = array();
        $alertbuttons[] = $mform->createElement('submit', 'yesbutton',
                get_string('alert_yes', 'block_ucla_copyright_status'));
        $alertbuttons[] = $mform->createElement('submit', 'laterbutton',
                get_string('alert_later', 'block_ucla_copyright_status'));
        $alertbuttons[] = $mform->createElement('submit', 'nobutton',
                get_string('alert_no', 'block_ucla_copyright_status'));
        $mform->addGroup($alertbuttons, 'alert_buttons', '', array(' '), false);
        $mform->closeHeaderBefore('alert_buttons');
    }
}