<?php
// This file is part of UCLA local plugin for Moodle - http://moodle.org/
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
 * Contains the extension of easy_upload_form for resources.
 *
 * @package    block_ucla_easyupload
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die;
/**
 * Extends easy_upload_forms using the type "resource".
 *
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class easyupload_resource_form extends easy_upload_form {
    /**
     * Override $allowpublicprivate in easy_upload_form to true.
     * @var boolean
     */
    public $allowpublicprivate = true;
    /**
     * Override $enableavailability in easy_upload_form to true.
     * @var boolean
     */
    public $enableavailability = false;

    /**
     * Initializes course and adds the correct elements to the form page. sets the moodleform.
     */
    public function specification() {
        $mform =& $this->_form;

        $course = $mform->getElement('course')->getValue();

        $mform->addElement('hidden', 'redirectme',
            '/course/modedit.php');
        $mform->setType('redirectme', PARAM_URL);

        $mform->addElement('select', 'add',
            get_string('dialog_add_resource_box', self::ASSOCIATED_BLOCK),
            $this->resources);
    }

    /**
     * Needs to implement abstract function.
     * @return boolean false
     */
    public function get_coursemodule() {
        return false;
    }

    /**
     * These are the parameters sent when the form wants to redirect.
     * @return array strings
     */
    public function get_send_params() {
        return array('course', 'add', 'section', 'private');
    }
}