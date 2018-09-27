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
 * Generate data needed to query courses for BrowseBy.
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
class browseby_courses extends base {
    /**
     * Format results in format specified in https://ucla.in/2Nr1uVN.
     *
     * @param array $params     Contains 'term' and 'subjectarea'.
     */
    public function build_result($params) {
        $retval = [];

        // Call /v1/Classes/{offeredTermCode}?subjectAreaCode={subjectAreaCode}.
        $classes = $this->query('Classes/' . $params['offeredTermCode'], $params, true);
        if (empty($classes)) {
            return null;
        }

        foreach ($classes as $class) {
            // Each section for a given catalog number is in termSessionGroupCollection.
            foreach ($class['termSessionGroupCollection'][0]['classCollection'] as $section) {
                $row = [];
                // Get data from Classes/{offeredTermCode}/{subjectAreaCode}/{courseCatalogNumber}/{classNumber}/ClassDetail.
                $classkey = $class['offeredTermCode'] . '/' . $class['subjectAreaCode'] .
                        '/' . $class['courseCatalogNumber'] . '/' . $section['classNumber'];
                $query = 'Classes/' . $classkey . '/ClassDetail';
                $classdetail = $this->query($query, null, true);
                if (empty($classdetail)) {
                    $this->debug(sprintf('No results for %s; skipping', $query));
                    continue;
                }

                // Get data from GET ClassSections/{offeredTermCode}/{subjectAreaCode}/{courseCatalogNumber}/{classSectionNumber}/ClassSectionDetail.
                $query = 'ClassSections/' . $classkey . '/ClassSectionDetail';
                $classsectiondetail = $this->query($query, null, true);
                if (empty($classsectiondetail)) {
                    $this->debug(sprintf('No results for %s; skipping', $query));
                    continue;
                }

                // Built row.
                $row['srs'] = $classsectiondetail['classSectionID'];
                $row['subjarea'] = $class['subjectAreaCode'];
                $row['course'] = util::format_cat_num($class['courseCatalogNumber']);
                $row['session'] = $classdetail['classSessionCode'];
                $row['section'] = $classsectiondetail['classSectionNumberDisplay'];
                $row['url'] = $classdetail['classWebsite'];
                $row['coursetitlelong'] = $classdetail['classTitle'];
                $row['activitytype'] = $classsectiondetail['classSectionActivityCode'];
                $row['sect_no'] = $classdetail['classNumber'];
                $row['catlg_no'] = $class['courseCatalogNumber'];
                $row['sect_enrl_stat_cd'] = $classsectiondetail['classSectionEnrollmentStatusCode'];
                $row['ses_grp_cd'] = $class['termSessionGroupCollection'][0]['termsessionGroupCode'];
                $retval[] = $row;                
            }
        }

        return $retval;
    }

    /**
     * Returns nothing since no parameters are needed.
     *
     * @return array
     */
    public function get_parameters() {
        return ['offeredTermCode', 'subjectAreaCode'];
    }
}
