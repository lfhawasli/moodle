<?php
// This file is part of the UCLA support console for Moodle - http://moodle.org/
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
 * @package    tool_uclasupportconsole
 * @copyright  2016 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version    = 2016030600;
$plugin->requires   = 2011022100;
$plugin->component  = 'tool_uclasupportconsole'; // Full name of the plugin (used for diagnostics).
$plugin->cron       = 86400;                     // Period for cron to check plugin (24 hours - in seconds).

$plugin->dependencies = array('local_ucla' => ANY_VERSION, 
                              'tool_uclacourserequestor' => ANY_VERSION);