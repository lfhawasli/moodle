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
 *  Defines the ucla_cp_module_push_grades class
 *
 * @package block_ucla_control_panel
 * @copyright  UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 **/


defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/admin_submodule.php');

/**
 *  Push grades module for ucla control panel
 *
 * @copyright  UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 **/
class ucla_cp_module_push_grades extends ucla_cp_module_admin {
    /**
     * Instantiates the parent class using the param
     *
     * @param stdClass $course A course object
     * @return void
     */
    public function __construct($course) {
        $param = array(
            'console' => 'pushgrades',
            'courseid' => $course->id
        );

        parent::__construct($param);
    }

    /**
     * Returns key
     *
     * @return string
     */
    public function get_key() {
        return 'push_grades';
    }
}
