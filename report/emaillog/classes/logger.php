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
 * Helper class to log outgoing forum emails.
 *
 * @package report_emaillog
 * @copyright  2015 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Class implementation.
 *
 * @copyright  2015 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_emaillog_logger {
    /**
     * Inserts a new email log record into the report_emaillog database.
     *
     * @param int $userid the recipient's user id
     * @param string $email the recipient's email address
     * @param int $post post id
     * @param boolean $emailstop indicated whether recipient has notifications off
     * @return boolean true on success
     */
    static public function store_email_log($userid, $email, $post, $emailstop) {
        global $DB;

        // The user has email notifications turned off.
        if ($emailstop) {
            return false;
        }

        // Sanity check on inputs.
        if (empty($userid) || empty($email) || is_null($post)) {
            return false;
        }

        // Check if logging is enabled.
        if (!get_config('report_emaillog', 'enable')) {
            return false;
        }

        // Create and insert database record.
        $record = new stdClass();
        $record->post = $post;
        $record->recipient_id = $userid;
        $record->recipient_email = $email;
        $record->timestamp = time();

        $DB->insert_record('report_emaillog', $record);
        return true;
    }
}