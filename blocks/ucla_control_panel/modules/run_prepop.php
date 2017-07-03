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
 *  This file defines the ucla_cp_module_run_prepop class
 *
 * @package block_ucla_control_panel
 * @copyright  UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 **/

defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/admin_submodule.php');

/**
 *  This class defines the run prepop module for the ucla control panel
 *
 * @copyright  UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 **/
class ucla_cp_module_run_prepop extends ucla_cp_module_admin {
    /**
     * Constructs your object
     * @param stdClass $course
     */
    public function __construct($course) {
        $param = array('console' => 'prepoprun',
                      'courseid' => $course->id);

        parent::__construct($param);
    }

    /**
     * Returns the module key
     * @return string
     */
    public function get_key() {
        return 'run_prepop';
    }
}
