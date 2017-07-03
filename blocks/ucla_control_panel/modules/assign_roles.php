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
 *  Defines the ucla_cp_module_assign_roles
 *
 * @package block_ucla_control_panel
 * @copyright  UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 **/
defined('MOODLE_INTERNAL') || die();

/**
 * Class for the assign roles module on the ucla control panel
 * @copyright  UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class ucla_cp_module_assign_roles extends ucla_cp_module {
    /**
     * Constructs your object
     * global string $CFG config option
     * @param stdClass $course course object
     * @param bool $home indicates home status
     */
    public function __construct($course, $home=false) {
        global $CFG;

        $this->action = new moodle_url($CFG->wwwroot . '/enrol/users.php',
            array('id' => $course->id));

        $this->home = $home;

        $this->shortname = $course->shortname;

        parent::__construct();

        if ($home) {
            $this->itemname .= '_master';
        }
    }

    /**
     * Defines the module tags
     * @return array
     */
    public function autotag() {
        return array('ucla_cp_mod_advanced');
    }

    /**
     * Defines the module capabilities
     * @return array
     */
    public function autocap() {
        return 'moodle/role:assign';
    }

    /**
     * Returns the module key
     * @return string
     */
    public function get_key() {
        if ($this->home) {
            $namer = 'assign_roles_0_' . $this->shortname;
        } else {
            $namer = 'assign_roles_1_' . $this->shortname;
        }

        return $namer;
    }
}
