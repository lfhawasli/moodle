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
require_once('../../bruincast_dbsync.php');
require_once(dirname(__FILE__) . '../../updatelib.php');

class update_bcast extends \core\task\scheduled_task {
   /**
    * A function that returns the name of the task being executed
    * 
    * @return string that indicates name of task
    */
    public function get_name() {
        return "Update Bruincast";
    }
    
   /**
    * Function to specify the code to be executed for this task
    * 
    */
    public function execute() {
        // Check to see if config variables are initialized.
        $sourceurl = get_config('block_ucla_bruincast', 'source_url');
        if (empty($sourceurl)) {
            log_ucla_data('bruincast', 'read', 'Initializing cfg variables',
                get_string('errbcmsglocation', 'tool_ucladatasourcesync') );
                die("\n".get_string('errbcmsglocation', 'tool_ucladatasourcesync')."\n");
        }
        print($sourceurl);
        // Begin database update.
        update_bruincast_db($sourceurl);
    }

}