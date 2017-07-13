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
 * Calls ucla_getterms stored procedure.
 *
 * @copyright 2012 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_ucla
 */

defined('MOODLE_INTERNAL') || die;
require_once(dirname(__FILE__) . '/registrar_stored_procedure.base.php');

/**
 * Calls ucla_getterms stored procedure.
 * 
 * @copyright   2012 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class registrar_ucla_getterms extends registrar_stored_procedure {
    /**
     * Validates results.
     *
     * @param array $new
     * @param array $old
     * @return boolean
     */
    public function validate($new, $old) {
        $tests = array(
                'term',
                'session',
                'session_start',
                'session_end',
                'instruction_start'
            );

        foreach ($tests as $criteria) {
            if (!isset($new[$criteria])) {
                return false;
            }
        }

        if (!ucla_validator('term', $new['term'])) {
            return false;
        }

        return true;
    }

    /**
     * Returns query params.
     *
     * @return array    array of strings
     */
    public function get_query_params() {
        return array('term');
    }
}
