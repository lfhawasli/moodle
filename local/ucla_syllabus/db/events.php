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
 * Database events.
 *
 * This file contains the event handlers for the Moodle event API.
 *
 * @package local_ucla_syllabus
 * @subpackage db
 * @copyright 2012 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = array (
    array(
        'eventname'  => '\local_ucla_syllabus\event\syllabus_added',
        'callback'    => 'local_ucla_syllabus_webservice_observer::ucla_syllabus_updated',
        'internal'   =>  false
    ),
    array(
        'eventname'  => '\local_ucla_syllabus\event\syllabus_updated',
        'callback'    => 'local_ucla_syllabus_webservice_observer::ucla_syllabus_updated',
        'internal'   =>  false
    ),
    array(
        'eventname'  => '\local_ucla_syllabus\event\syllabus_deleted',
        'callback'    => 'local_ucla_syllabus_webservice_observer::ucla_syllabus_deleted',
        'internal'   =>  false
    ),
    array(
        'eventname'  => '\core\event\course_created',
        'callback'    => 'local_ucla_syllabus_webservice_observer::ucla_course_alert',
        'internal'   =>  false
    ),
    array(
        'eventname'  => '\core\event\course_deleted',
        'callback'    => 'local_ucla_syllabus_observer::delete_syllabi',
        'internal'   =>  true
    ),
);
