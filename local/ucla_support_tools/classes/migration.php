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
 * Handles the importing and exporting functionality of the UCLA support tools.
 *
 * @package    local_ucla_support_tools
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class file
 *
 * @package    local_ucla_support_tools
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ucla_support_tools_migration {

    /**
     * Table names that need to be can be imported and exported.
     * @var array
     */
    static $tables = array('ucla_support_tools', 'ucla_support_categories',
        'ucla_support_tool_categories', 'ucla_support_tags', 'ucla_support_tool_tags');

    /**
     * Exports tool information, including categories and tags, into JSON string.
     *
     * @return string   JSON encoded PHP array of tool information.
     */
    public static function export() {
        global $DB;

        // Query tools and categories and encode data into JSON object.
        $export = array();
        foreach (self::$tables as $table) {
            $export[$table] = $DB->get_records($table);
        }

        return json_encode($export);
    }

    /**
     * Performs validation of backup JSON and tries to empty and insert records into
     * the tools database.
     *
     * @param string $backupjson
     * @return boolean              Returns false if there were any errors in importing.
     */
    public static function import($backupjson) {
        global $DB;
        $backup = self::validate_import($backupjson);
        if (empty($backup)) {
            return false;
        }

        // Turn on database transactions so that we can rollback on any errors.
        try {
            $transaction = $DB->start_delegated_transaction();

            // Empty given table and then insert records.
            foreach (self::$tables as $table) {
                $DB->execute('TRUNCATE TABLE {' . $table . '}');
                if (!empty($backup[$table])) {
                    // Do bulk insert.
                    $DB->insert_records($table, $backup[$table]);
                }
            }

            $transaction->allow_commit();
        } catch (dml_exception $dmle) {
            $transaction->rollback($dmle);
            return false;
        }

        return true;
    }

    /**
     * Parses and validates backup data.
     *
     * @param string $backupjson
     *
     * @return mixed    Returns backup JSON as array if valid, else returns false.
     */
    public static function validate_import($backupjson) {
        $contents = json_decode($backupjson, true);
        if (empty($contents)) {
            return false;
        }

        // Make sure that import data has all table names defined.
        foreach (self::$tables as $table) {
            if (!isset($contents[$table])) {
                return false;
            }
        }
        return $contents;
    }
}