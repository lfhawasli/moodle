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
 * Extends CRUD with tool relation organization operations.
 */
abstract class local_ucla_support_tools_organizer extends local_ucla_support_tools_crud {

    const TABLE_RELATION = '';
    const TABLE_RELATION_ID = '';

    /**
     * All organizers have colors.
     * 
     * @var type string HTML HEX color value.
     */
    public $color;

    protected function __construct($data) {
        parent::__construct($data);

        // @todo: Enforce data integrity, c
        if (empty($this->color)) {
            throw new Exception('Missing color');
        }
    }

    /**
     * Deletes tool associations.
     */
    public function delete() {

        // Remove tool associations.
        foreach ($this->get_tools() as $tool) {
            $this->remove_tool($tool);
        }

        // Finally, delete self.
        parent::delete();
    }

    /**
     * Get all the tools under this organizer.
     * 
     * @return array of \local_ucla_support_tools_tool
     */
    public function get_tools() {
        global $DB;

        if (empty($this->id)) {
            throw new Exception('Record has not been stored');
        }

        $tools = array();

        try {
            // Order tools by favorite status, if possible, and name.
            $sql = "SELECT tool.* 
                      FROM {ucla_support_tools} AS tool
                      JOIN {" . static::TABLE_RELATION . "} AS rel ON tool.id = rel.toolid
                     WHERE rel." . static::TABLE_RELATION_ID . " = ?
                  ORDER BY tool.name";
            $records = $DB->get_records_sql($sql, array($this->id));

            $favorites = $others = array();
            foreach ($records as $data) {
                $tool = \local_ucla_support_tools_tool::fetch($data);
                if ($tool->is_favorite()) {
                    $favorites[] = $tool;
                } else {
                    $others[] = $tool;
                }
            }

            $tools = array_merge($favorites, $others);
        } catch (Exception $ex) {
            // Return empty array.
        }

        return $tools;
    }

    /**
     * Adds a tool to organizer.
     * 
     * @param \local_ucla_support_tools_tool $tool
     */
    public function add_tool(\local_ucla_support_tools_tool $tool) {
        global $DB;

        if (empty($this->id)) {
            throw new Exception('Record has not been stored');
        }

        if (empty($tool->id)) {
            throw new Exception('Tool does not have ID');
        }

        if (!$DB->record_exists(static::TABLE_RELATION, array(static::TABLE_RELATION_ID => $this->id, 'toolid' => $tool->get_id()))) {
            return $DB->insert_record(static::TABLE_RELATION, array(static::TABLE_RELATION_ID => $this->id, 'toolid' => $tool->get_id()));
        }

        return null;
    }

    /**
     * Removes a tool from organizer.
     * 
     * @param \local_ucla_support_tools_tool $tool
     */
    public function remove_tool(\local_ucla_support_tools_tool $tool) {
        global $DB;

        if ($DB->record_exists(static::TABLE_RELATION, array(static::TABLE_RELATION_ID => $this->id, 'toolid' => $tool->get_id()))) {
            return $DB->delete_records(static::TABLE_RELATION, array(static::TABLE_RELATION_ID => $this->id, 'toolid' => $tool->get_id()));
        }

        return null;
    }

}
