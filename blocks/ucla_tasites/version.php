<?php
// This file is part of the UCLA TA sites block for Moodle - http://moodle.org/
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
 * Version file.
 *
 * @package    block_ucla_tasites
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$plugin->version = 2018101900;

// Add dependency for meta courses, local_ucla (for flash), and site indicator
// (for tasite type).
$plugin->dependencies = array(
    'enrol_meta'  => ANY_VERSION,
    'local_metagroups' => ANY_VERSION,
    'local_ucla'  => 2012112800,
    'tool_uclasiteindicator' => 2013021900
);
$plugin->component = 'block_ucla_tasites';
