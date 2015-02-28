<?php
// This file is part of the UCLA support tools plugin for Moodle - http://moodle.org/
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
 * Exports the UCLA support tools listing and categories.
 *
 * @package    local_ucla_support_tools
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');

// Needs user to be logged in.
require_login();
require_capability('local/ucla_support_tools:edit', context_system::instance());

// Send data as file to user.
@header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
@header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
@header('Pragma: no-cache');
header("Content-Type: application/download\n");
header("Content-Disposition: attachment; filename=\"supporttoolexport.txt\"");

echo local_ucla_support_tools_migration::export();;

die();