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
 * Contains the hidden srs view form for the course requestor.
 *
 * @package    tool_uclacourserequestor
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__) . '/requestor_view_form.php');

/**
 * Requestor view form for hidden srs requests.
 *
 * @package    tool_uclacourserequestor
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class requestor_hidden_srs_view_form extends requestor_view_form {
    /**
     * Determines which string to use as the submit button.
     * @var string
     */
    public $type = 'viewrequest';
    /**
     * Returns an array of mForm elements to attach into the group.
     * @return array
     */
    public function specification() {
        if (!isset($this->_customdata['srs'])) {
            $this->_customdata['srs'] = '';
        }

        $group = array();
        $group[] =& $this->_form->createElement('hidden', 'srs',
            $this->_customdata['srs']);
        $group[] =& $this->_form->createElement('hidden', 'term',
            $this->_customdata['selterm']);

        return $group;
    }
    /**
     * Returns the set of courses that should respond to the request method
     * and parameters. Called after all the data has been verified.
     * @param object $data responses from the fetch form.
     * @return array Sets of course-term-srs sets
     */
    public function respond($data) {
        // Override which fields to check.
        $this->_customdata['prefields'] = array(
                'srs' => array(),
                'term' => array()
            );

        return parent::respond($data);
    }

    /**
     * Don't print yourself, but just return yourself.
     * @return string
     */
    public function display() {
        return $this->_form->toHtml();
    }
}
