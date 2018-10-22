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

require_once($CFG->dirroot . '/local/ucla/classes/rolling-curl/RollingCurl.php');

/**
 * Class file
 *
 * @package    local_ucla
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class browseby_courses extends base {

    /**
     * Processes the RollingCurl callback.
     *
     * @param string $result    JSON data from webservice.
     * @param mixed $info       Data from curl_getinfo.
     */
    protected function multi_query_process($result, $info) {
        // Ignore extra data.
        if (is_array($result)) {
            $result = reset($result);
        }

        // Processing either ClassDetail or ClassSectionDetail, for section
        // number ClassDetail uses classNumber and ClassSectionDetail uses
        // classSectionNumber.
        $keys = [$result['subjectAreaCode'], $result['courseCatalogNumber']];
        if (strpos($info['url'], 'ClassDetail')) {
            $keys[] = $result['classNumber'];
            $query = 'ClassDetail';
        } else {
            $keys[] = $result['classSectionNumber'];
            $query = 'ClassSectionDetail';
        }
        $this->callbackstorage[$this->get_index($keys)][$query] = $result;
    }

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

                $this->multi_query_add($query);

                // Get data from GET ClassSections/{offeredTermCode}/{subjectAreaCode}/{courseCatalogNumber}/{classSectionNumber}/ClassSectionDetail.
                $query = 'ClassSections/' . $classkey . '/ClassSectionDetail';

                $this->multi_query_add($query);

                // Built row.
                $row['subjarea'] = $class['subjectAreaCode'];
                $row['course'] = util::format_cat_num($class['courseCatalogNumber']);
                $row['catlg_no'] = $class['courseCatalogNumber'];
                $row['sect_no'] = $section['classNumber'];
                $row['ses_grp_cd'] = $class['termSessionGroupCollection'][0]['termsessionGroupCode'];
                $retval[] = $row;            
            }
        }

        // Execute parallel API calls.
        $this->multi_query_execute();

        // Go through results and build remaining data.
        foreach ($retval as $i => $row) {
            // Find record.
            $index = $this->get_index([$row['subjarea'], $row['catlg_no'], $row['sect_no']]);

            $record = $this->validate_callbackstorage($index, ['ClassSectionDetail', 'ClassDetail']);
            if ($record === false) {
                // Entry does not have data from everything, so remove it.
                unset($retval[$i]);
                continue;
            }

            $row['srs'] = $record['ClassSectionDetail']['classSectionID'];
            $row['session'] = $record['ClassDetail']['classSessionCode'];
            $row['section'] = $record['ClassSectionDetail']['classSectionNumberDisplay'];
            $row['url'] = $record['ClassDetail']['classWebsite'];
            $row['coursetitlelong'] = $record['ClassDetail']['classTitle'];
            $row['activitytype'] = $record['ClassSectionDetail']['classSectionActivityCode'];
            $row['sect_enrl_stat_cd'] = $record['ClassSectionDetail']['classSectionEnrollmentStatusCode'];

            $retval[$i] = $row;
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
