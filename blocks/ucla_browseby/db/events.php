<?php
// This file is part of the UCLA browseby block for Moodle - http://moodle.org/
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
 * Event handlers.
 *
 * @package    block_ucla_browseby
 * @copyright  2016 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

$observers = array(
    array(
        'eventname' => '\tool_uclacoursecreator\event\course_creator_finished',
        'callback'  => 'block_ucla_browseby_observer::browseby_sync_courses'
    ),

    array(
        'eventname' => '\core\event\ucla_course_deleted',
        'callback'  => 'block_ucla_browseby_observer::browseby_sync_deleted'
    )
);
