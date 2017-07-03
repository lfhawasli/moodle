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
 *  Defines the ucla_cp_text_module class
 *
 * @package block_ucla_control_panel
 * @copyright  UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 **/
defined('MOODLE_INTERNAL') || die();

/**
 * This class is a text module for the ucla control panel
 *
 * This module is meant to be used in order to display pure text (no links)
 * within the control panel. The itemname of the module represents the pure text.
 * @copyright  UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 **/
class ucla_cp_text_module extends ucla_cp_module{
    /**
     * Constructs your object
     * @param string $itemname
     * @param array $tags
     * @param string $capability
     * @param array $options
     * @return void
     */
    public function __construct($itemname=null,  $tags=null,
            $capability=null, $options=null) {

        parent::__construct($itemname, null, $tags, $capability, $options);
        // This function does not used localized strings, so default false makes more sense.
        $this->options['post'] = false;
    }

    /**
     * Workaround so the renderer recognizes this as text.
     * @return false
     */
    public function is_tag() {
        return false;
    }
}
