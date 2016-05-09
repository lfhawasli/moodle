<?php
// This file is part of the UCLA TA sites block for Moodle - http://moodle.org/
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
 * Generator class to help in the writing of Behat tests for the TA sites plugin.
 *
 * @package    block_ucla_tasites
 * @category   test
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Behat\Context\Step\Given as Given,
    Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Mink\Exception\ExpectationException as ExpectationException,
    Behat\Mink\Exception\DriverException as DriverException,
    Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;

/**
 * Behat custom steps.
 *
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_ucla_tasites extends behat_base {
    /**
     * Creates the necessary entry in the cache so that
     * block_ucla_tasites::get_tasection_mapping() works properly.
     *
     * @Given /^the following TA mapping exist:$/
     *
     * @param TableNode $data
     * @throws ExpectationException
     */
    public function following_ta_mapping_exist(TableNode $data) {
        global $DB;

        // Create mapping array, indexed by courseid.
        $mappings = array();
        foreach ($data->getHash() as $elementdata) {
            $term = $elementdata['term'];
            $parentsrs = $elementdata['parentsrs'];
            $courseid = ucla_map_termsrs_to_courseid($term, $parentsrs);
            if (empty($courseid)) {
                throw new ExpectationException('Term and parentsrs must match an existing course',
                    $this->getSession());
            }
            $mappings[$courseid]['term'] = $term;

            // See if there's a uid passed.
            if (isset($elementdata['uid'])) {
                $uid = $elementdata['uid'];
                if (!empty($uid)) {
                    $user = $DB->get_record('user', array('idnumber' => $uid));
                    $fullname = fullname($user);
                }
            }

            // See if section srs are passed.
            if (isset($elementdata['secnum']) && isset($elementdata['secsrs'])) {
                $secnum = $elementdata['secnum'];
                $secsrs = $elementdata['secsrs'];
            }

            // Setup TA information.
            if (!empty($fullname)) {
                $mappings[$courseid]['byta'][$fullname]['ucla_id'] = $uid;
                if (!empty($secsrs)) {
                    $mappings[$courseid]['bysection'][$secnum]['tas'][$uid] = $fullname;
                }
            }

            // Section srs is optional. If not passed, then assume course
            // has no sections.
            if (empty($secsrs)) {
                $mappings[$courseid]['bysection']['all']['secsrs'][] = $parentsrs;
            } else {
                $mappings[$courseid]['byta'][$fullname]['secsrs'][$secnum][] = $secsrs;
                $mappings[$courseid]['bysection'][$secnum]['secsrs'][] = $secsrs;
            }
        }

        $cache = cache::make('block_ucla_tasites', 'tasitemapping');
        foreach ($mappings as $courseid => $mapping) {
            $cache->set($courseid, $mapping);
        }
    }

}