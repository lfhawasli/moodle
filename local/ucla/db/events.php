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
    'course_creator_finished' => array(
        'handlerfile'     => '/local/ucla/eventslib.php',
        'handlerfunction' => 'ucla_sync_built_courses',
        'schedule'        => 'instant'
    ),
    'course_restored' => array(
        'handlerfile'     => '/local/ucla/eventslib.php',
        'handlerfunction' => 'course_restored_enrol_check',
        'schedule'        => 'instant'
    ),
    'course_restored' => array(
        'handlerfile'     => '/local/ucla/eventslib.php',
        'handlerfunction' => 'course_restored_dedup_default_forums',
        'schedule'        => 'instant'
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
