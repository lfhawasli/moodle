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
 * Represents a UCLA support tool.
 */
class local_ucla_support_tools_tool extends local_ucla_support_tools_crud implements renderable {

    const TABLE = 'ucla_support_tools';

    public $url;
    public $description;
    public $docs_url;
    public $created;
    public $updated;
    public $metadata;

    /**
     * Creates a new tool.
     * 
     * @param array $data
     * @throws Exception
     */
    protected function __construct($data) {

        parent::__construct($data);

        // Needs to have URL.
        if (empty($this->url)) {
            throw new Exception('Missing URL');
        }
        // Try to make URL relative.
        $moodleurl = new moodle_url($this->url);
        $this->url = $moodleurl->out_as_local_url(false);
        // Trim description.
        $this->description = trim($this->description);
    }

    /**
     * Creates a new tool record.
     */
    protected function create_record() {

        // Required fields for tool creation.
        $this->created = time();
        $this->updated = $this->created;

        return parent::create_record();
    }

    /**
     * Updates tool last modified timestamp.
     */
    public function update() {

        $this->updated = time();
        parent::update();
    }

    /**
     * Deletes a tool as well as associations with tags and categories.
     */
    public function delete() {

        // Delete tool from categories.
        foreach ($this->get_categories() as $cat) {
            $cat->remove_tool($this);
        }

        // Delete tool from tags.
        foreach ($this->get_tags() as $tag) {
            $tag->remove_tool($this);
        }

        // Finally delete self.
        parent::delete();
    }

    /**
     * Add a tag to a tool.
     * 
     * @param \local_ucla_support_tools_tag $tag
     */
    public function add_tag(\local_ucla_support_tools_tag $tag) {
        $tag->add_tool($this);
    }

    /**
     * Gets tags for a tool.
     */
    public function get_tags() {
        global $DB;

        $tags = array();

        try {
            $sql = "SELECT tag.* FROM {ucla_support_tags} AS tag "
                    . "JOIN {ucla_support_tool_tags} AS rel ON tag.id = rel.tagid "
                    . "WHERE rel.toolid = ?";

            $records = $DB->get_records_sql($sql, array($this->id));

            foreach ($records as $k => $val) {
                $tags[] = \local_ucla_support_tools_tag::fetch($val->id);
            }
        } catch (Exception $ex) {
            // Return empty array.
        }

        return $tags;
    }

    /**
     * Gets categories for tool
     */
    public function get_categories() {
        global $DB;

        $tools = array();

        try {
            $sql = "SELECT cat.* FROM {ucla_support_categories} AS cat "
                    . "JOIN {ucla_support_tool_categories} AS rel ON cat.id = rel.categoryid "
                    . "WHERE rel.toolid = ?";

            $records = $DB->get_records_sql($sql, array($this->id));

            foreach ($records as $k => $val) {
                $tools[] = \local_ucla_support_tools_category::fetch($val->id);
            }
        } catch (Exception $ex) {
            // Return empty array.
        }

        return $tools;
    }

}
