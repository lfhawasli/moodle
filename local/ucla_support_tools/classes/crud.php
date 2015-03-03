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
 * Abstract class that implements CRUD operations for UCLA support tools.
 */
abstract class local_ucla_support_tools_crud {

    const TABLE = '';

    /**
     * Record ID.
     * 
     * @var int
     */
    protected $id;

    /**
     * All tools have names. 
     * 
     * @var string
     */
    public $name;

    /**
     * Constructor should only be called by class.
     * 
     * @param array $data
     */
    protected function __construct($data) {
        $this->id = null;

        // Get valid properties
        $validprops = array_keys(get_object_vars($this));

        // Map fields to properties
        foreach ($data as $k => $val) {
            if (in_array($k, $validprops)) {
                $this->{$k} = trim($val);
            }
        }
    }

    public function get_id() {
        return $this->id;
    }

    /**
     * Creates a new record.
     * 
     * @return int ID of record created.
     */
    protected function create_record() {
        global $DB;

        if (!empty($this->id)) {
            return $this;
        }

        $record = get_object_vars($this);
        $this->id = $DB->insert_record(static::TABLE, $record);
        return $this;
    }

    /**
     * Deletes a record
     * 
     * @return boolean
     * @throws Exception
     */
    public function delete() {
        global $DB;

        if (empty($this->id)) {
            throw new Exception('Invalid record');
        }

        return $DB->delete_records(static::TABLE, array('id' => $this->id));
    }

    /**
     * Updates an object and saves state to DB.
     * 
     * @throws Exception
     */
    public function update() {
        global $DB;

        $record = get_object_vars($this);
        return $DB->update_record(static::TABLE, $record);
    }

    /**
     * Creates a new object and stores record.
     * 
     * @return \class
     */
    public static function create($data) {

        $class = get_called_class();
        $created = new $class($data);
        return $created->create_record();
    }

    /**
     * Fetch all objects.
     * 
     * @return \class
     */
    public static function fetch_all() {
        global $DB;

        $records = $DB->get_records(static::TABLE, null, 'name ASC');

        $all = array();

        // Needed for late binding of class name.
        $class = get_called_class();

        foreach ($records as $rec) {
            $all[] = new $class($rec);
        }

        return $all;
    }

    /**
     * Fetch an object with given $id
     * 
     * @param int | object $data
     * @return \class | null if object does not exist.
     */
    public static function fetch($data) {
        global $DB;

        // If we're getting a valid DB record, we can build obj.
        if (is_object($data) && !empty($data->id)) {
            $class = get_called_class();
            return new $class($data);
        }

        try {
            $record = $DB->get_record(static::TABLE, array('id' => $data));
            $class = get_called_class();
            return new $class($record);
        } catch (Exception $ex) {
            return null;
        }
    }

}
