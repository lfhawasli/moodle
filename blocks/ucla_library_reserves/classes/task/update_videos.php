<?php
// This file is part of the UCLA Library Reserves block for Moodle - http://moodle.org/
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

namespace block_ucla_library_reserves\task;
require_once('../../libraryreserves_dbsync.php');
require_once('../../updatelib.php');


/**
 * Upgrades database for library reserves
 *
 * @package    block_ucla_library_reserves
 * @author     Anant Mahajan
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_videos extends \core\task\scheduled_task {

   /**
    * A function that returns the name of the task being executed
    * 
    * @return string that indicates name of task
    */
    public function get_name() {
        return "Update Library Reserves";
    }
   
   /**
    * Function to specify the code to be executed for this task
    * 
    */
    public function execute() {
        // Check to see that config variable is initialized.
        $datasourceurl = get_config('block_ucla_library_reserves', 'source_url');
        if (empty($datasourceurl)) {
            log_ucla_data('library reserves', 'read', 'Initializing cfg variables',
                    get_string('errlrmsglocation', 'tool_ucladatasourcesync'));
                die("\n" . get_string('errlrmsglocation', 'tool_ucladatasourcesync') . "\n");
        }

        // Begin database update.
        update_libraryreserves_db($datasourceurl);
    }

}