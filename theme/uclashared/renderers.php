<?php
// This file is part of the UCLA theme plugin for Moodle - http://moodle.org/
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
 * Override Moodle's core renderers.
 *
 * @package    theme_uclashared
 * @copyright  UC Regents 2014
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/theme/uclashared/renderers/core_backup_renderer.php');
require_once($CFG->dirroot . '/theme/uclashared/renderers/core_calendar_renderer.php');
require_once($CFG->dirroot . '/theme/uclashared/renderers/core_course_renderer.php');
require_once($CFG->dirroot . '/theme/uclashared/renderers/core_enrol_renderer.php');
require_once($CFG->dirroot . '/theme/uclashared/renderers/core_management_renderer.php');
require_once($CFG->dirroot . '/theme/uclashared/renderers/core_renderer.php');