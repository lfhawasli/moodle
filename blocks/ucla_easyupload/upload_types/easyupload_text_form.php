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
 * Contains the extension of easy_upload_form for text forms.
 *
 * @package    block_ucla_easyupload
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

/**
 * Extends easy_upload_forms using the type "text".
 *
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class easyupload_text_form extends easy_upload_form {
    /**
     * Override $allowjsselect in easy_upload_form to true.
     * @var boolean
     */
    public $allowjsselect = true;

    /**
     * Sets the moodleform and adds necessary elements to the page.
     */
    public function specification() {
        $mform =& $this->_form;

        $mform->addElement('editor', 'introeditor', get_string('dialog_add_text_box', self::ASSOCIATED_BLOCK),
                array('rows' => 3), array('maxfiles' => EDITOR_UNLIMITED_FILES,
                'noclean' => true, 'context' => $this->context, 'collapsed' => true));
    }

    /**
     * Returns the course module, which is just 'label'.
     * @return string 'label'
     */
    public function get_coursemodule() {
        return 'label';
    }
}