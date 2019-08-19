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
 * UCLA local plugin cron task.
 *
 * Contains the settings for UCLA specific customizations.
 *
 * @package    local_ucla
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => 'local_ucla\task\local_ucla_cron_task',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '0', // Midnight.
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),
    array(
        'classname' => 'local_ucla\task\purge_users_quiz_attempts_task',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '0', // Midnight.
        'day' => '1,15', // 1st and 15th of every month
        'dayofweek' => '*',
        'month' => '*'
    )
);
