<?php
// This file is part of Moodle - http://moodle.org/
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

/*
 * Helper class to log outgoing forum emails.
 *
 * @package report_emaillog
 * @copyright  2015 UC Regents
 */

$settings->add(new admin_setting_configtext('report_emaillog/daysexpire',
        get_string('daysexpire', 'report_emaillog'),
        get_string('daysexpire_desc', 'report_emaillog'), 7, PARAM_INT));
