<?php
// This file is part of the UCLA local_ucla plugin for Moodle - http://moodle.org/
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
 * Redirect users to combined enrolled users and participants page.
 *
 * @copyright 2012 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_ucla
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the connection with the Registrar. NOTHING MORE.
 */
class registrar_tester extends registrar_query {
    /**
     * Returns false.
     *
     * @param array $new
     * @param array $old
     *
     * @return boolean
     */
    public function validate($new, $old) {
        return false;
    }

    /**
     * Returns false.
     *
     * @param array $args
     * @return boolean
     */
    public function remote_call_generate($args) {
        return false;
    }
}
