<?php
// This file is part of the UCLA local_ucla plugin for Moodle - http://moodle.org/
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
 * Clear syllabus links from SRDB.
 *
 * @package    local_ucla
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ucla\task;
defined('MOODLE_INTERNAL') || die();

/**
 * Task class.
 *
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clear_srdb_ucla_syllabus_task extends \core\task\adhoc_task {

    /**
     * Executes the task.
     *
     * @throws Exception on error
     */
    public function execute() {
        $customdata = parent::get_custom_data();

        // Clear syllabi for each ucla_reg_classinfo entry.
        $regsender = new \local_ucla_regsender();
        $links = array('public' => '', 'private' => '', 'protect' => '');

        foreach ($customdata->ucla_reg_classinfo as $classinfo) {
            $result = $regsender->set_syllabus_link($classinfo->term,
                    $classinfo->subj_area, $classinfo->crsidx, $classinfo->classidx,
                    $links);
            if ($result == \local_ucla_regsender::FAILED) {
                // Error, try again later.
                return false;
            }
        }

        return true;
    }

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('clear_srdb_ucla_syllabus_task', 'local_ucla');
    }
}
