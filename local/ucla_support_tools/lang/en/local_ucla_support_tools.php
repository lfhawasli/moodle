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

/**
 * UCLA support tools plugin.
 *
 * @package    local_ucla_support_tools
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['backhome'] = 'Back to support tools';
$string['importerror'] = 'Could not import support tools from import file. Nothing changed.';
$string['importsuccess'] = 'Successfully overwrote existing tools, categories, and tags with data from import file.';
$string['importtitle'] = 'Import UCLA support tools';
$string['importwarning'] = 'Importing file will overwrite existing tool, categories, and tags with data from import file. Favorite selections should not be affected.';
$string['pluginname'] = 'UCLA support tools';
$string['mysiteslink'] = 'View all support tools';

// Capabilities
$string['ucla_support_tools:view'] = 'View the UCLA support tools';
$string['ucla_support_tools:edit'] = 'Edit the UCLA support tools';