<?php
// This file is part of the UCLA theme plugin for Moodle - http://moodle.org/
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

require_once($CFG->dirroot.'/enrol/renderer.php');

/**
 * Table control used for enrolled users
 *
 */
class local_ucla_course_enrolment_users_table extends course_enrolment_users_table {

    /**
     * Sets the fields for this table. These get added to the tables head as well.
     *
     * You can also use a multi dimensional array for this to have multiple fields
     * in a single column
     *  
     * This leaves out the checkbox field when bulk enrolment operations are present; 
     * local/ucla/customscripts/enrol/users.php takes care of that
     *
     * @param array $fields An array of fields to set
     * @param string $output
     */
    public function set_fields($fields, $output) {
        parent::set_fields($fields, $output);
        if (!empty($this->bulkoperations)) {
            // If there are bulk operations add a column for checkboxes.
            unset($this->head[0]);
            unset($this->colclasses[0]);
        }
    }

    /**
     * Sets the users for this table
     *
     * @param array $users
     * @return void
     */
    public function set_users(array $users) {
        parent::set_users($users);
        if ($hasbulkops = !empty($this->bulkoperations)) {
            foreach ($this->data as $row) {
                    unset($row->cells[0]);
            }
        }
    }

    /**
     * Returns true if the table is aware of any bulk operations that can be performed on users
     * selected from the currently filtered enrolment plugins.
     *
     * @return bool
     */
    public function has_bulk_operations() {
        return $this->has_bulk_user_enrolment_operations() ||
                has_capability('moodle/course:bulkmessaging', $this->manager->get_context());
    }

}
