<?php
// This file is part of the UCLA browse-by plugin for Moodle - http://moodle.org/
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
 * Class file to handle Browse-By division listings.
 *
 * @package    block_ucla_browseby
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class definition for browsing by division.
 *
 * @package    block_ucla_browseby
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class division_handler extends browseby_handler {
    /**
     * Returns what parameters are required for this handler.
     */
    public function get_params() {
        return array();
    }

    /**
     *  A list of divisions.
     *
     * @param array $args
     **/
    public function handle($args) {
        global $OUTPUT;

        $s = '';
        $t = get_string('division_title', 'block_ucla_browseby');

        // This is the parameters for one of the two possible query
        // types in this function...
        $params = array();

        $term = $args['term'];
        $params['term'] = $term;

        $sql = "
        SELECT DISTINCT
            CONCAT(di.code, rci.term) AS rsetid,
            di.code,
            di.fullname,
            rci.term
        FROM {ucla_reg_division} di
        INNER JOIN {ucla_reg_classinfo} rci
            ON rci.division = di.code
        WHERE rci.term = :term
        ORDER BY di.fullname
        ";

        $divisions = $this->get_records_sql($sql, $params);

        $s .= block_ucla_browseby_renderer::render_terms_selector(
            $args['term']);

        if (empty($divisions)) {
            $s .= $OUTPUT->notification(get_string('division_noterm',
                    'block_ucla_browseby'));
            return array($t, $s);
        } else {
            $table = $this->list_builder_helper($divisions, 'code',
                'fullname', 'subjarea', 'division');
        }

        $s .= block_ucla_browseby_renderer::ucla_custom_list_render(
            $table);

        return array($t, $s);
    }
}
