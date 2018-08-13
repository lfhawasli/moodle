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
 * @package   block_ucla_office_hours
 * @copyright 2018 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/block_ucla_office_hours.php');

/**
 * Adds office hours link to admin panel.
 *
 * @param navigation_node $navigation The navigation node to extend.
 * @param stdClass        $course     The course object for the tool.
 * @param context         $context    The context of the course.
 */
function block_ucla_office_hours_extend_navigation_course($navigation, $course, $context) {
    global $USER;

    if (block_ucla_office_hours::allow_editing($context, $USER->id)) {
        $setting = navigation_node::create(get_string('editofficehours', 'block_ucla_office_hours'),
                new moodle_url('/blocks/ucla_office_hours/officehours.php',
                array('courseid' => $course->id, 'editid' => $USER->id)),
                navigation_node::TYPE_SETTING, null, 'editofficehours');

        $navigation->add_node($setting);
    }
};
