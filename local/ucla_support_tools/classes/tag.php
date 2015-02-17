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

/**
 * Represents a UCLA tool tag.
 */
class local_ucla_support_tools_tag extends local_ucla_support_tools_organizer implements renderable {

    const TABLE = 'ucla_support_tags';
    const TABLE_RELATION = 'ucla_support_tool_tags';
    const TABLE_RELATION_ID = 'tagid';

    public static function fetch($name) {
        global $DB;

        if (is_number($name) || is_int($name)) {
            return parent::fetch($name);
        }

        try {
            $record = $DB->get_record(static::TABLE, array('name' => trim($name)));
            $class = get_called_class();
            return new $class($record);
        } catch (Exception $ex) {
            return null;
        }
    }

}
