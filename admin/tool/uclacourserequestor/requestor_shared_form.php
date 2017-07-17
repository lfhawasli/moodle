<?php
// This file is part of the UCLA course creator plugin for Moodle - http://moodle.org/
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
 * Contains the abstract class requestor_shared_form.
 *
 * @package    tool_uclacourserequestor
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Abstract class for the uclacourserequestor forms which extends moodleform.
 *
 * @package    tool_uclacourserequestor
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class requestor_shared_form extends moodleform {
    /**
     * Determines which string to use as the submit button.
     * @var string
     */
    public $type = null;

    /**
     * Hack for conveniently not displaying terms for which there are no requests.
     * @var boolean
     */
    public $noterm = false;

    /**
     * The name of the group in the quickform.
     * @var string
     */
    public $groupname = 'requestgroup';

    /**
     * Attributes to prevent JavaScript refresh warning.
     * @var string
     */
    public $attributes = 'onChange="M.core_formchangechecker.set_form_submitted(); this.form.submit()"';

    /**
     * The moodleform definition. Calls post_specification() when it is done.
     */
    public function definition() {
        $mform =& $this->_form;

        $term = $this->_customdata['selterm'];
        $terms = $this->_customdata['terms'];

        $requestline = array();

        $ucr = 'tool_uclacourserequestor';
        $gn = $this->groupname;

        if (!$this->noterm) {
            $requestline[] =& $mform->createElement('select', 'term', null,
                $terms, $this->attributes);
        }

        $specline = $this->specification();
        if (is_array($specline)) {
            $requestline = array_merge($requestline, $specline);
        }

        $requestline[] =& $mform->createElement('submit', 'submit',
             get_string($this->type, $ucr));

        $mform->addGroup($requestline, $gn, null, ' ', true);
        $mform->setType('requestgroup[srs]', PARAM_ALPHANUM);
        $mform->setType('requestgroup[term]', PARAM_ALPHANUM);
        $mform->setDefaults(
            array(
                $gn => array(
                    'term' => $term
                )
            )
        );

        $this->post_specification();
    }

    /**
     * Returns an array of mForm elements to attach into the group.
     * Please override.
     * @return boolean false
     */
    public function specification() {
        return false;
    }

    /**
     * Adds additional functionality after the group has been added to the
     * quick form.
     * Please override.
     * @return boolean false
     */
    public function post_specification() {
        return false;
    }

    /**
     * Returns the set of courses that should respond to the request method
     * and parameters. Called after all the data has been verified.
     * This function probably breaks a lot of OO-boundaries.
     * @param object $data responses from the fetch form.
     * @return Array Sets of course-term-srs sets
     */
    public function respond($data) {
        return array();
    }
}