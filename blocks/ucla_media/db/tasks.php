<?php
// This file is part of the UCLA Media block for Moodle - http://moodle.org/
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
 * Upgrades database for bruincast
 *
 * @package    block_ucla_media
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$tasks = array(
    // Run at 10:30 am and 8:30 pm.
    array(
        'classname' => 'block_ucla_media\task\update_bcast',
        'blocking' => 0,
        'minute' => '30',
        'hour' => '10,20',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),
    // Run at 10:15 am and 8:15 pm.
    array(
        'classname' => 'block_ucla_media\task\update_vidreserves',
        'blocking' => 0,
        'minute' => '15',
        'hour' => '10,20',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    )
);