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
     * Caches current url value. Used when we call update to see if URL changed,
     * thus meaning that we need to update the favorite values for all users.
     * @var string
     */
    private $_originalurl;

    /**
     * Creates a new tool.
     * 
     * @param array $data
     * @throws Exception
     */
    protected function __construct($data) {
        parent::__construct($data);

        // If description was not passed, then just set it to blank.
        if (is_array($data) && !isset($data['description'])) {
            $this->description = '';
        }

        // Need to do special case handling for URL.
        $this->handle_url();
    }

    /**
     * Creates a new tool record.
     */
    protected function create_record() {

        // Required fields for tool creation.
        $this->created = time();
        $this->updated = $this->created;

        try {
            return parent::create_record();
        } catch (dml_write_exception $e) {
            // Error has to be from violating unique key restriction on url.
            throw new Exception('Duplicate URL');
        }
    }

    /**
     * Updates tool information. If tool url changes, then we need to update
     * the related favorited tool entries.
     *
     * @throws Exception
     */
    public function update() {
        global $DB;

        // Validate and clean up url.
        $this->handle_url();

        if ($this->url != $this->_originalurl) {
            // Get list of current users using old url hash.
            $oldhash = $this->get_url_hash($this->_originalurl);
            $records = $DB->get_records_select('user_preferences', 'name = :name AND ' .
                    $DB->sql_like('value', ':value'),
                    array('name'  => 'local_ucla_support_tools_favorites',
                          'value' => "%$oldhash%"));
            if (!empty($records)) {
                $newhash = $this->get_url_hash();
                foreach ($records as $record) {
                    // Do a simple string replacement to update url hash. No
                    // need to do json decode and encode.
                    $record->value = str_replace($oldhash, $newhash, $record->value);

                    // Call API and not update DB directly so that Moodle caches
                    // are notified appropriately.
                    set_user_preference('local_ucla_support_tools_favorites',
                            $record->value, $record->userid);
                }
            }
        }

        $this->updated = time();
        parent::update();

        $this->_originalurl = $this->url;
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
    
    /**
     * Gets metadata for tool
     * 
     * @return stdClass
     */
    public function get_metadata() {
        return json_decode($this->metadata);
    }
    
    /**
     * Sets key/value pairs in metadata for tool
     */
    public function set_metadata($key, $value) {
        $metadata = $this->get_metadata();
        $metadata->$key = $value;
        $this->metadata = json_encode($metadata);
    }

    /**
     * Will return URL hash used when saving user's favorites.
     *
     * @param string $url   URL to hash. If null, then hash current url value.
     * @return string   Returns the first 7 characters from a sha1 hash.
     */
    public function get_url_hash($url = null) {
        if (empty($url)) {
            $url = $this->url;
        }

        // See: http://blog.cuviper.com/2013/11/10/how-short-can-git-abbreviate/
        // on why it is okay to use 7 characters.
        $urlhash = sha1($url);
        return substr($urlhash, 0, 7);
    }

    /**
     * Checks if tool is marked as a favorite for the current user.
     *
     * @return boolean
     */
    public function is_favorite() {
        // Get user preference for given tool, using URL as the unique key.
        $favjson = get_user_preferences('local_ucla_support_tools_favorites', null);

        if (empty($favjson)) {
            $this->isfavorite = false;
        } else {
            // Should be an JSON array of URL hashes.
            $favarray = array();
            if (!empty($favjson)) {
                $favarray = json_decode($favjson);
            }

            if (in_array($this->get_url_hash(), $favarray)) {
                $this->isfavorite = true;
            } else {
                $this->isfavorite = false;
            }
        }

        return $this->isfavorite;
    }

    /**
     * Toggles the favorite state of given tool for the current user.
     *
     * Note that user preferences table can only hold values up to 1333
     * characters. So that is why we need to hash tool urls. A user can have
     * around 150 or so tools favorited.
     *
     * We also want to hash tool urls instead of tool ids, because we want to
     * be able to import/export tool listing around different servers and ids
     * may not be consistent, but URLs are consistent.
     *
     * @boolean Returns current status of favorite status. True if favorited,
     *          false if not favorited.
     */
    public function toggle_favorite() {
        global $USER;

        // Need to check if we need to add or remove tool from favorites list.
        $isfavorite = $this->is_favorite();
        $favjson = get_user_preferences('local_ucla_support_tools_favorites');
        $favarray = array();
        if (!empty($favjson)) {
            $favarray = json_decode($favjson);
        }
        
        if ($isfavorite) {
            // Remove tool by reemoving url hash from array.
            $favarray = array_diff($favarray, array($this->get_url_hash()));
            // Need to make sure that array is reindexed back to zero, because
            // if index starts at anything else it will be decoded into object
            // and also take up more character space.
            $favarray = array_values($favarray);
        } else {
            // Need to add as a favorite.
            $favarray[] = $this->get_url_hash();
        }

        $savedfavs = null;
        if (!empty($favarray)) {
            $favarray = array_unique($favarray);
            $savedfavs = json_encode($favarray);
        }
        set_user_preference('local_ucla_support_tools_favorites', $savedfavs);
        $this->isfavorite = !$isfavorite;  // Update cache.

        return $this->isfavorite;
    }

    /**
     * Makes sure object URL is a relative url when possible.
     *
     * @throws Exception
     */
    private function handle_url() {
        if (empty($this->url)) {
            throw new Exception('Missing URL');
        }

        // Try to make URL relative if it is on the same server. Else, keep link
        // to external server.
        try {
            $moodleurl = new moodle_url($this->url);
            // Will throw an exception if url is not a local path.
            $this->url = $moodleurl->out_as_local_url(false);
        } catch (Exception $e) {
            $this->url = $this->url;
        }

        // Save original URL in case it changes later.
        if (!isset($this->_originalurl)) {
            $this->_originalurl = $this->url;
        }
    }

    /**
     * Fetching all tools should show favorite tools first.
     * 
     * @return array
     */
    public static function fetch_all() {
        $tools = parent::fetch_all();
        $favtools = array();

        foreach ($tools as $k => $tool) {
            if ($tool->is_favorite()) {
                $favtools[] = $tool;
                unset($tools[$k]);
            }
        }

        return array_merge($favtools, $tools);
    }
    /**
     * Returns all favorite tools for current user.
     * 
     * @return type
     */
    public static function fetch_favorites() {
        $tools = self::fetch_all();
        $favtools = array();
        foreach ($tools as $tool) {
            if ($tool->is_favorite()) {
                $favtools[] = $tool;
            }
        }
        return $favtools;
    }
}
