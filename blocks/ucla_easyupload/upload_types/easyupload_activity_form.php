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
 * Contains the extension of easy_upload_form for activities.
 *
 * @package    block_ucla_easyupload
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * This could be abstracted out another level.
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class easyupload_activity_form extends easy_upload_form {
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
     * Initializes course and handles the activities.
     */
    public function specification() {
        $mform =& $this->_form;

        $course = $mform->getElement('course')->getValue();

        $mform->addElement('hidden', 'redirectme', '/course/modedit.php');
        $mform->setType('redirectme', PARAM_URL);

        // Add the select form.
        $actsel = $mform->addElement('select', 'add',
            get_string('dialog_add_activity_box', self::ASSOCIATED_BLOCK));

        // We need to specially handle activities.
        // Due to nested types... this MAY be needed for resources too
        // Iteration of recursion...
        foreach ($this->activities as $cursor => $actname) {
            $prefix = '-';
            if (is_array($actname)) {
                $temp = array_keys($actname);
                $cursor = reset($temp);
                $actname = reset($actname);
            }

            $tempstack = array(
                array($cursor => $actname)
            );

            while (!empty($tempstack)) {
                $temppop = array_pop($tempstack);

                $objname = reset($temppop);
                $temppop = array_keys($temppop);
                $objref = reset($temppop);

                if (is_array($objname)) {
                    $tempstack[] = array($prefix);

                    foreach ($objname as $subcur => $subact) {
                        $tempstack[] = array($subcur => $subact);
                    }

                    $tempstack[] = array($prefix . ' ' . $objref);
                    $prefix .= '-';
                } else {

                    if ($objname[0] == '-') {
                        $actsel->addOption($objname, '',
                            array('disabled' => 'disabled'));
                    } else {
                        $actsel->addOption($objname, $objref);
                    }
                }
            }
        }
    }

    /**
     * No coursemodule associated with this form.
     * @return boolean false
     */
    public function get_coursemodule() {
        return false;
    }

    /**
     * Required for redirection forms.
     * @return string array
     */
    public function get_send_params() {
        return array('course', 'add', 'section', 'private');
    }
}