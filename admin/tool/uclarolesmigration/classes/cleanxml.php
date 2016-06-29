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
 * Used for sanitizing xml before role import.
 * 
 * This file contains an extenstion of the core_role_present class,
 * that allows &nbsp; characters in the role definition XML schemes.
 * Therefore users are now able to import roles from XML files with
 * &nbsp; in them.
 *
 * @package    tool_uclarolesmigration
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Used for sanitizing xml before role import.
 * 
 * Class extends core_role_present and reimplements two methods with
 * input being sanitized by replacing &nbsp; characters in xml code.
 *
 * @package    tool_uclarolesmigration
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_uclarolesmigration_cleanxml extends core_role_preset {
    
    /**
     * See if an xml file is valid
     *
     * @param string $xml
     * @return bool depending on whether $xml is valid
     */
    public static function is_valid_preset($xml) {
        $oldstring = "&nbsp;";
        $newstring = "&#160;";
        $xml = str_replace($oldstring , $newstring, $xml);
        return parent::is_valid_preset($xml);
    }
    
    /**
     * Parse role preset xml file.
     *
     * @param string $xml
     * @return array role info, null on error
     */
    public static function parse_preset($xml) {
        $oldstring = "&nbsp;";
        $newstring = "&#160;";
        $xml = str_replace($oldstring , $newstring, $xml);
        return parent::parse_preset($xml);
    }
}
