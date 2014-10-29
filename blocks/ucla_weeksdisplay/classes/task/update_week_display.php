<?php

// This file is part of the UCLA weeks display block for Moodle - http://moodle.org/
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
 * Task execution definition for weeks display.
 *
 * @package    block_ucla_weeksdisplay
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_ucla_weeksdisplay\task;

global $CFG;
require_once($CFG->dirroot . '/blocks/ucla_weeksdisplay/block_ucla_weeksdisplay.php');

/**
 * Task that will update the weeks display.
 */
class update_week_display extends \core\task\scheduled_task {

    /**
     * Task 'friendly' name
     * 
     * @return string
     */
    public function get_name() {
        // Shown in admin screens.
        return get_string('task', 'block_ucla_weeksdisplay');
    }

    /**
     * Action to execute.
     */
    public function execute() {

        \block_ucla_weeksdisplay::set_current_week_display();
    }

}
