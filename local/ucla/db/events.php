<?php
// This file is part of the UCLA local plugin for Moodle - http://moodle.org/
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
 * This file contains the event handlers for the Moodle event API.
 * 
 * @package local_ucla
 * @copyright 2013 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$handlers = array(
    'ucla_course_deleted' => array(
        'handlerfile'     => '/local/ucla/eventslib.php',
        'handlerfunction' => 'clear_srdb_ucla_syllabus',
        'schedule'        => 'cron',
        'internal'         => 0,
    ),
    'ucla_syllabus_added' => array (
        'handlerfile'     => '/local/ucla/eventslib.php',
        'handlerfunction'  => 'update_srdb_ucla_syllabus',
        'schedule'         => 'cron',
        'internal'         => 0,
    ),
    'ucla_syllabus_deleted' => array (
        'handlerfile'     => '/local/ucla/eventslib.php',
        'handlerfunction'  => 'update_srdb_ucla_syllabus',
        'schedule'         => 'cron',
        'internal'         => 0,
    ),
    'ucla_weeksdisplay_changed' => array(
        'handlerfile'     => '/local/ucla/eventslib.php',
        'handlerfunction' => 'hide_past_courses',
        'schedule'        => 'instant'
    ),
);

$observers = array(
    array(
        'eventname'  => '\block_ucla_weeksdisplay\event\week_changed',
        'callback'    => 'local_ucla_observer::hide_past_courses',
    ),
    array(
        'eventname'   => '\core\event\course_restored',
        'callback'    => 'local_ucla_observer::course_restored_dedup_default_forums',
    ),
    array(
        'eventname'   => '\core\event\course_restored',
        'callback'    => 'local_ucla_observer::course_restored_enrol_check',
    ),
    array(
        'eventname'   => '\core\event\user_loggedout',
        'callback'    => 'local_ucla_autologin::clear',
    ),
    array(
        'eventname'   => '\tool_uclacoursecreator\event\course_creator_finished',
        'callback'    => 'local_ucla_observer::ucla_sync_built_courses',
    ),
);
