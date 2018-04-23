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
 * Alert form definition.
 *
 * @package    block_ucla_my_sites
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Form class.
 *
 * Used to create a form for dismissing the notification
 * regarding an alternate email.
 *
 * @copyright   2012 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class my_sites_form extends moodleform {
    /**
     * Generates the button on the my_sites block for dismissing the notification
     * about whether the user has an alternate email set or not.
     */
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('submit', 'dismissbutton', get_string('dismiss', 'block_ucla_my_sites'));
    }
}

