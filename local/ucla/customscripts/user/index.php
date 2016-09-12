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
 * Redirect users to combined enrolled users and participants page.
 *
 * @copyright 2015 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_ucla
 */

if (local_ucla_core_edit::using_ucla_theme()) {
    $contextid    = optional_param('contextid', 0, PARAM_INT); // One of this or.
    $courseid     = optional_param('id', 0, PARAM_INT); // This are required.

    if (!empty($contextid) && empty($courseid)) {
        $context = context::instance_by_id($contextid, MUST_EXIST);
        if ($context->contextlevel != CONTEXT_COURSE) {
            print_error('invalidcontext');
        }
        $courseid = $context->instanceid;
    }

    redirect(new moodle_url('/enrol/users.php', array('id' => $courseid)));
}
