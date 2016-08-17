<?php
// This file is part of the UCLA Media block for Moodle - http://moodle.org/
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
 * Upgrades database for bruincast
 *
 * @package    block_ucla_media
 * @author     Anant Mahajan
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_ucla_media\task;
require_once('../../videoreserves_dbsync.php');

class update_vidreserves extends \core\task\scheduled_task {
   
   /**
    * Function to specify the code to be executed for this task
    * 
    */
    public function execute() {
        // Get video reserves. Dies if there are any problems.
        $videoreserves = get_videoreserves();

        // Begin database update.
        update_videoreserves_db($videoreserves);
    }
   /**
    * A function that returns the name of the task being executed
    * 
    * @return string that indicates name of task
    */
    public function get_name() {
        return "Update Video Reserves";
    }

}