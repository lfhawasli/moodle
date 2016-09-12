<?php
// This file is part of the UCLA public/private plugin for Moodle - http://moodle.org/
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
 * Definition of local_publicprivate handlers and observers.
 *
 * @package    local_publicprivate
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$observers = array(
    array(
        'eventname' => '\core\event\course_created',
        'callback'  => '\local_publicprivate\observers::course_created'
    ),
    array(
        'eventname' => '\core\event\course_updated',
        'callback'  => '\local_publicprivate\observers::course_updated'
    ),
    array(
        'eventname' => '\core\event\course_module_created',
        'callback'  => '\local_publicprivate\observers::course_module_created'
    ),
    array(
        'eventname' => '\block_ucla_group_manager\event\section_groups_synced',
        'callback'  => '\local_publicprivate\observers::section_groups_synced'
    ),
    array(
        'eventname' => '\core\event\user_enrolment_updated',
        'callback'  => '\local_publicprivate\observers::user_enrolment_updated'
    ),
    array(
        'eventname' => '\core\event\role_assigned',
        'callback'  => '\local_publicprivate\observers::role_assigned'
    ),
    array(
        'eventname' => '\core\event\role_unassigned',
        'callback'  => '\local_publicprivate\observers::role_unassigned'
    )
);
