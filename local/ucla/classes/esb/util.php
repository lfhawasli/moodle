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
 * Class containing useful utility methods for ESB functions.
 *
 * @package    local_ucla
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ucla\esb;

defined('MOODLE_INTERNAL') || die();

/**
 * Class file
 *
 * @package    local_ucla
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class util {
    /**
     * Formats courseCatalogNumber to display format:
     *     0000SSPP -> PP . int(0000) . SS
     *
     * @param string catnum
     *
     * @return string           Returns formatted string.
     */
    public static function format_cat_num($catnum) {
        $num = intval(substr($catnum, 0, 4));

        if (strlen($catnum) < 5) {
            $ss = '  ';
        } else {
            if (strlen($catnum) < 6) {
                $ss = $catnum[4] . ' ';
            } else {
                $ss = $catnum[4] . $catnum[5];
            }
        }

        if (strlen($catnum) < 7) {
            $pp = '  ';
        } else {
            if (strlen($catnum) < 8) {
                $pp = $catnum[6] . ' ';
            } else {
                $pp = $catnum[6] . $catnum[7];
            }
        }

        return trim(trim($pp) . $num . trim($ss));
    }

    /**
     * Returns all available ESB queries
     *
     * @return array        Array of ESB queries available
     */
    public static function get_queries() {
        global $CFG;

        $dirname = $CFG->dirroot .'/local/ucla/classes/esb';
        $qfs = glob($dirname . '/*.php');

        $queries = array();
        foreach ($qfs as $query) {
            if ($query == __FILE__) {
                continue;
            }
            $query = str_replace($dirname . '/', '', $query);
            $query = str_replace('.php', '', $query);
            // Ignore the base and tester classes.
            if ($query == 'base' || $query == 'tester') {
                continue;
            }
            $queries[] = $query;
        }
        return $queries;
    }
}
