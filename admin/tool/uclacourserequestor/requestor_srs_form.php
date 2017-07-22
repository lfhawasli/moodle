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
 * Contains the srs form for the course requestor.
 *
 * @package    tool_uclacourserequestor
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

require_once(dirname(__FILE__) . '/requestor_shared_form.php');

/**
 * Form for requesting courses based on course srs.
 *
 * @package    tool_uclacourserequestor
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class requestor_srs_form extends requestor_shared_form {
    /**
     * Determines which string to use as the submit button.
     * @var string
     */
    public $type = 'buildcourse';

    /**
     * Returns an array of mForm elements to attach into the group.
     * @return array
     */
    public function specification() {
        $mform =& $this->_form;

        $spec = array();

        $srs[] =& $mform->createElement('text', 'srs', null,
            array('size' => '25'));

        return $srs;
    }
    /**
     * Adds additional functionality after the group has been added to the
     * quick form.
     */
    public function post_specification() {
        $mform =& $this->_form;

        $mform->addGroupRule($this->groupname,
            array(
                'srs' => array(
                    array(
                        get_string('srserror', 'tool_uclacourserequestor'),
                            'regex', '/^[0-9]{9}$/', 'client'
                    )
                )
            )
        );
    }
    /**
     * Returns the set of courses that should respond to the request method
     * and parameters. Called after all the data has been verified.
     * @param object $data responses from the fetch form.
     * @return array Sets of course-term-srs sets
     */
    public function respond($data) {
        require_once(dirname(__FILE__) . '/ucla_courserequests.class.php');

        $ci = $data->{$this->groupname};

        $hc = get_request_info($ci['term'],
                ucla_courserequests::get_main_srs($ci['term'], $ci['srs'])
                );

        if ($hc === false) {
            return $hc;
        } else if ($hc) {
            $set = get_crosslist_set_for_host($hc);
        } else {
            return array();
        }

        return array($set);
    }
}