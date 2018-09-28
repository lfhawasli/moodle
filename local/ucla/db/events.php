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

defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname'  => '\local_ucla_syllabus\event\syllabus_added',
        'callback'    => 'local_ucla_observer::update_srdb_ucla_syllabus',
    ),
    array(
        'eventname'  => '\local_ucla_syllabus\event\syllabus_deleted',
        'callback'    => 'local_ucla_observer::update_srdb_ucla_syllabus',
    ),
    array(
        'eventname'  => '\tool_uclacoursecreator\event\ucla_course_deleted',
        'callback'    => 'local_ucla_observer::clear_srdb_ucla_syllabus',
    ),
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
    array(
        'eventname'   => '\core\event\course_module_created',
        'callback'    => 'local_ucla_turnitintwo::sync_assignments',
    ),
    array(
        'eventname'   => '\core\event\role_assigned',
        'callback'    => 'local_ucla_turnitintwo::sync_assignments',
    ),
    array(
        'eventname'   => '\core\event\role_unassigned',
        'callback'    => 'local_ucla_turnitintwo::sync_assignments',
    ),
    array(
        'eventname'   => '\core\event\course_restored',
        'callback'    => 'local_ucla_turnitintwo::sync_assignments',
    ),
);
