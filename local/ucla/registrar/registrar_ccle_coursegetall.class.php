<?php
// This file is part of the UCLA local plugin for Moodle - http://moodle.org/
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
 * Enabled calling of ccle_courseGetAll SP.
 *
 * @package    local_ucla
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once(dirname(__FILE__) . '/registrar_query.base.php');

/**
 * Enabled calling of ccle_courseGetAll SP.
 * 
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class registrar_ccle_coursegetall extends registrar_stored_procedure {

    /**
     * Returns query params.
     *
     * @return array
     */
    public function get_query_params() {
        return array('term', 'subjarea');
    }

    /**
     * Returns stored procedure name.
     *
     * @return string
     */
    public function get_stored_procedure() {
        return 'ccle_courseGetAll';
    }
}

