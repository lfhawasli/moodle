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
 * Calls ucla_get_user_classes_test stored procedure.
 *
 * @copyright 2012 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_ucla
 */

defined('MOODLE_INTERNAL') || die;
require_once(dirname(__FILE__) . '/registrar_stored_procedure.base.php');

/**
 * Calls ucla_get_user_classes_test stored procedure.
 */
class registrar_ucla_get_user_classes extends registrar_stored_procedure {
    /**
     * Do not trim catlg_no.
     *
     * @var array
     */
    protected $notrim = array('catlg_no');

    /**
     * Returns query params.
     *
     * @return array    array of strings
     */
    public function get_query_params() {
        return array('uid');
    }

    /**
     * Returns stored procedure name.
     *
     * @return string
     */
    public function get_stored_procedure() {
        return 'ucla_get_user_classes_test';
    }
}
