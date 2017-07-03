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
 * Contains the extension of easy_upload_form for links.
 *
 * @package    block_ucla_easyupload
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

/**
 * Extends easy_upload_forms using the type "link".
 *
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class easyupload_link_form extends easy_upload_form {
    /**
     * Override $allowrenaming in easy_upload_form to true.
     * @var boolean
     */
    public $allowrenaming = true;
    /**
     * Override $allowjssselect in easy_upload_form to true.
     * @var boolean
     */
    public $allowjsselect = true;

    /**
     * Adds the necessary elements to the form and sets the default URL redirection. Sets the moodleform.
     */
    public function specification() {
        $mform =& $this->_form;

        $mform->addElement('url', 'externalurl',
            get_string('dialog_add_link_box', self::ASSOCIATED_BLOCK),
            array('size' => 60), array('usefilepicker' => false));
        $mform->setType('externalurl', PARAM_URL);

        $mform->addRule('externalurl', null, 'required');

        // Set default URL redirection.
        $mform->setDefault('display', get_config('url', 'display'));
    }

    /**
     * Returns the course module, which is just 'url'.
     * @return string 'url'
     */
    public function get_coursemodule() {
        return 'url';
    }
}
