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
 * Generate SRS for a course.
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
class get_course_srs extends base{
    /**
     * Format results in format specified in https://ucla.in/2Nr1uVN.
     *
     * @param array $params     Contains 'term', 'subjectarea', 'coursecatalognumber' and 'classnumber'.
     * @return array $row.
     */
    public function build_result($params) {
        // Call /v1/ClassSections/{offeredTermCode}/{subjectAreaCode}/{courseCatalogNumber}/{classSectionNumber}/ClassSectionDetail.
        $classes = $this->query('ClassSections/' . $params['offeredTermCode'] . '/' . $params['subjectAreaCode'] . '/' .  $params['courseCatalogNumber']
                . '/' . $params['classNumber'] . '/ClassSectionDetail', null, true);
        if (empty($classes)) {
            return null;
        }
        $row = [];
        $row['srs_crs_no'] = $classes['classSectionID'];

        return array($row);
    }

    /**
     * Returns nothing since no parameters are needed.
     *
     * @return array
     */
    public function get_parameters() {
        return ['offeredTermCode', 'subjectAreaCode', 'courseCatalogNumber', 'classNumber'];
    }

}
