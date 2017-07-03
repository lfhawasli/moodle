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
 * This file defines ucla_cp_module_easyupload class
 * @package block_ucla_control_panel
 * @copyright  UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();

/**
 * This class it the easyupload module for the ucla control panel
 * @copyright  UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class ucla_cp_module_easyupload extends ucla_cp_module {

    /**
     * Constructs your object by constructing the parent class
     * @param object $submodule
     */
    public function __construct($submodule) {
        global $CFG;

        // Let all the auto__() functions handle it.
        parent::__construct($submodule);
    }

    /**
     * Validates parent as well as class vars
     * @return boolean
     */
    public function validate() {
        if (!parent::validate()) {
            return false;
        }

        // We need to make sure that we actually have the ability to use
        // easy upload and such.
        global $CFG;

        // Cheap caching hack.
        $euexists = false;
        // You can manually check this, but the following FS check shouldn't
        // be too expensive.
        if (isset($CFG->control_panel_easyupload_link_established)) {
            $euexists = $CFG->control_panel_easyupload_link_established;
        } else {
            $easyuploadpath = $CFG->dirroot
                . '/blocks/ucla_easyupload/block_ucla_easyupload.php';

            if (file_exists($easyuploadpath)) {
                require_once($easyuploadpath);

                $euexists = true;
            } else {
                $euexists = false;
            }
        }

        if ($euexists) {
            $name = $this->get_key();
            $type = str_replace('add_' , '', $name);

            // This one is probably more expensive.
            return block_ucla_easyupload::upload_type_exists($type);
        }

        return false;
    }

    /**
     * Returns the tags for this module
     * @return array
     */
    public function autotag() {
        return array('ucla_cp_mod_common');
    }
    /**
     * Returns the capabilities for this module
     * @return string
     */
    public function autocap() {
        return 'moodle/course:manageactivities';
    }
}
