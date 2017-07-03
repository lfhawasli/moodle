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
 *  This file defines the ucla_cp_muucla_row_module class
 *
 * @package block_ucla_control_panel
 * @copyright  UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 **/
// This module is meant to be used in order to display an individual class's
// myucla links.

defined('MOODLE_INTERNAL') || die();
/**
 *  This class displays MyUCLA links on the control panel
 *
 *  This module is meant to be used in order to display an individual class's
 *  myucla links.
 * @package block_ucla_control_panel
 * @copyright  UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 **/
class ucla_cp_myucla_row_module extends ucla_cp_module {


    /** @var elements
     * This is an array of ucla_cp_modules that contains the elements of this row.
     */
    public $elements;

    /**
     * Constructs the parent object
     * @param array $tags
     * @param string $capability
     * @param array $options
     */
    public function __construct($tags = null, $capability = null, $options = null) {
        parent::__construct(null, null, $tags, $capability, $options);
    }

    /**
     * Adds element to the end of the row.
     * @param ucla_cp_module $element
     * @return void
     */
    public function add_element($element) {
        $this->elements[] = $element;
    }

    /**
     * Returns an array containing the elements in the row.
     * @return array
     */
    public function get_elements() {
        return $this->elements;
    }

    /**
     * Workaround so that it's not being treated as a tag.
     * @return false
     */
    public function is_tag() {
        return false;
    }

}
