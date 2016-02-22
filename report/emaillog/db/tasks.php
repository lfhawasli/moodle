<?php
// This file is part of the UCLA report_emaillog for Moodle - http://moodle.org/
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
 * UCLA report_emaillog cron task.
 *
 * Contains the settings for removing old email logs.
 *
 * @package    report_emaillog
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$tasks = array(
    array(
        'classname' => 'report_emaillog\task\report_emaillog_cron_task',
        'blocking' => 0,
        'minute' => '*',
        'hour' => '0', // Midnight.
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    )
);
