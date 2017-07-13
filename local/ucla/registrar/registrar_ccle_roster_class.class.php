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
 * Calls stored procedure ccle_roster_class.
 *
 * @package     local_ucla
 * @copyright   2012 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once(dirname(__FILE__) . '/registrar_cacheable_stored_procedure.base.php');

/**
 * Calls stored procedure ccle_roster_class.
 * 
 * @copyright   2012 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class registrar_ccle_roster_class extends registrar_cacheable_stored_procedure {
    /**
     * Returns query params.
     *
     * @return array
     */
    public function get_query_params() {
        return array('term', 'srs');
    }

    /**
     * Returns columns that will be in the results.
     *
     * @return string
     */
    public function get_result_columns() {
        return array('term_cd', 'stu_id', 'full_name_person', 'enrl_stat_cd',
            'ss_email_addr', 'bolid');
    }
}
