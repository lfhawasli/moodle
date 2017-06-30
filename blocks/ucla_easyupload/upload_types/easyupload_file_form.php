<?php
// This file is part of UCLA Easy Upload plugin for Moodle - http://moodle.org/
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
 * Contains the extension of easy_upload_form for files.
 *
 * @package    block_ucla_easyupload
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

/**
 * Extends easy_upload_forms using the type "file".
 *
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class easyupload_file_form extends easy_upload_form {
    /**
     * Override $allowrenaming in easy_upload_form to true.
     * @var boolean
     */
    public $allowrenaming = true;
    /**
     * Override $allowjsselect in easy_upload_form to true.
     * @var boolean
     */
    public $allowjsselect = true;

    /**
     * Initializes course, adds necessary elements to the page, and sets upload parameters. Sets the moodleform.
     */
    public function specification() {
        global $CFG;
        $mform =& $this->_form;

        $mform->addElement('static', 'upload' , '',
                html_writer::span(
                    html_writer::link(
                            $CFG->wwwroot . '/help.php?component=block_ucla_easyupload&identifier=bulkupload&lang=en',
                            get_string('upload', 'block_ucla_easyupload'),
                            array('aria-haspopup' => 'true', 'target' => '_blank', 'title' => 'Help with Bulk upload files')),
                        'helptooltip'
                    )
                );
        $mform->addHelpButton('upload', 'bulkupload', 'block_ucla_easyupload');

        // Important to call this before the file upload.
        $maxfilesize = get_max_upload_file_size($CFG->maxbytes, $this->course->maxbytes);
        // CCLE-3833: For some reason, to fix this ticket the value of
        // 2147483648> (2GB>) somehow causes an integer overflow. So we just
        // set it to 2147483647 (-1). This problem only appears to happen for
        // the "uclafile" form type and not the filepicker.
        if ($maxfilesize >= 2147483648) {
            $maxfilesize = 2147483647;
        }

        $filemanageropts = array();
        $filemanageropts['accepted_types'] = '*';
        $filemanageropts['maxbytes'] = $maxfilesize;
        $filemanageropts['maxfiles'] = 1;
        $filemanageropts['mainfile'] = true;

        $mform->addElement('filemanager', 'files', get_string('dialog_add_file', self::ASSOCIATED_BLOCK), null, $filemanageropts);
        $mform->addRule('files', '', 'required');
    }

    /**
     * Simplified version of mod_resource_mod_form:data_preprocessing()
     * from /mod/resource/mod_form:160 in Moodle 2.5.1
     *
     * Removed display options that are not included in this form.
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        if ($this->current->instance and !$this->current->tobemigrated) {
            $draftitemid = file_get_submitted_draft_itemid('files');
            file_prepare_draft_area($draftitemid, $this->context->id, 'mod_resource', 'content', 0, array('subdirs' => true));
            $defaultvalues['files'] = $draftitemid;
        }
    }

    /**
     * Returns the course module, which is just 'resource'.
     * @return string 'resource'
     */
    public function get_coursemodule() {
        return 'resource';
    }
}