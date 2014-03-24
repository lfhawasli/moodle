<?php
// This file is part of the UCLA library research portal plugin for Moodle - http://moodle.org/
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
 * Tests the UCLA site menu hook get_navigation_nodes().
 *
 * @package    block_ucla_library_portal
 * @category   phpunit
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/blocks/ucla_library_portal/block_ucla_library_portal.php');

/**
 * PHPunit testcase class.
 *
 * @package    block_ucla_library_portal
 * @category   phpunit
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class navigation_node_test extends advanced_testcase {

    /**
     * Creates test course and setups default forums.
     */
    protected function setUp() {
        $this->resetAfterTest(true);
    }

    /**
     * Make sure that no nodes are returned for non-registrar courses.
     */
    function test_nonregcourse() {
        $course = $this->getDataGenerator()->create_course();
        $nodes = block_ucla_library_portal::get_navigation_nodes(array('course' => $course));
        $this->assertEquals(0, count($nodes));
    }

    /**
     * Make sure that the default url is returned for a registrar course that
     * if no url is set in the site config.
     */
    function test_nourlset() {
        $class = $this->getDataGenerator()
                      ->get_plugin_generator('local_ucla')
                      ->create_class(array('term' => '14W'));
        $requestclass = array_pop($class);

        $course = get_course($requestclass->courseid);

        $nodes = block_ucla_library_portal::get_navigation_nodes(
                array('course' => $course));
        $this->assertEquals(1, count($nodes));

        $moodleurl = $nodes[0]->action;
        $this->assertEquals('http://www.library.ucla.edu/library-research-portal',
                $moodleurl->out());
    }

    /**
     * Make sure that if block_ucla_library_portal|url is set, that the proper
     * GET parameters are returned.
     */
    function test_regcourse() {
        set_config('url', 'http://ucla.edu', 'block_ucla_library_portal');

        $class = $this->getDataGenerator()
                      ->get_plugin_generator('local_ucla')
                      ->create_class(array());
        $requestclass = array_pop($class);

        $course = get_course($requestclass->courseid);

        $nodes = block_ucla_library_portal::get_navigation_nodes(
                array('course' => $course));
        $this->assertEquals(1, count($nodes));

        $moodleurl = $nodes[0]->action;
        $this->assertEquals('http://ucla.edu', $moodleurl->out_omit_querystring());

        // Will return variables in $c<num> naming scheme. Each variable should
        // have the following keys: t (term), sub (subject area), cat (catalog
        // number), and sec (section number).
        parse_str($moodleurl->get_query_string(false));

        $reginfos = ucla_get_course_info($requestclass->courseid);
        $reginfo = array_pop($reginfos);

        $this->assertEquals($reginfo->term, $c0['t']);
        $this->assertEquals($reginfo->subj_area, $c0['sub']);
        $this->assertEquals($reginfo->crsidx, $c0['cat']);
        $this->assertEquals($reginfo->classidx, $c0['sec']);
    }

    /**
     * Make sure that if block_ucla_library_portal|url is set, that the proper
     * GET parameters are returned for cross-listed courses.
     */
    function test_regcourse_crosslisted() {
        set_config('url', 'http://ucla.edu', 'block_ucla_library_portal');

        $class = $this->getDataGenerator()
                      ->get_plugin_generator('local_ucla')
                      ->create_class(array(array(), array()));
        $requestclass = array_pop($class);

        $course = get_course($requestclass->courseid);

        $nodes = block_ucla_library_portal::get_navigation_nodes(
                array('course' => $course));
        $this->assertEquals(1, count($nodes));

        $moodleurl = $nodes[0]->action;
        $this->assertEquals('http://ucla.edu', $moodleurl->out_omit_querystring());

        // Will return variables in $c<num> naming scheme. Each variable should
        // have the following keys: t (term), sub (subject area), cat (catalog
        // number), and sec (section number).
        parse_str($moodleurl->get_query_string(false), $results);
        $this->assertEquals(2, count($results));

        $reginfos = ucla_get_course_info($requestclass->courseid);
        foreach ($reginfos as $index => $reginfo) {
            $this->assertEquals($reginfo->term, $results['c'.$index]['t']);
            $this->assertEquals($reginfo->subj_area, $results['c'.$index]['sub']);
            $this->assertEquals($reginfo->crsidx, $results['c'.$index]['cat']);
            $this->assertEquals($reginfo->classidx, $results['c'.$index]['sec']);
        }
    }

    /**
     * Make sure that if block_ucla_library_portal|maxrecords is set, that more
     * results than maxrecords are returned.
     */
    function test_regcourse_maxrecords() {
        set_config('url', 'http://ucla.edu', 'block_ucla_library_portal');
        set_config('maxrecords', 4, 'block_ucla_library_portal');

        // Create course with 4 crosslisted sections.
        $class = $this->getDataGenerator()
                      ->get_plugin_generator('local_ucla')
                      ->create_class(array(array(), array(), array(), array()));
        $requestclass = array_pop($class);

        $course = get_course($requestclass->courseid);

        $nodes = block_ucla_library_portal::get_navigation_nodes(
                array('course' => $course));
        $this->assertEquals(1, count($nodes));

        // Count how many records are returned.
        $moodleurl = $nodes[0]->action;
        parse_str($moodleurl->get_query_string(false), $results);
        $this->assertEquals(4, count($results));

        // Now lower the number of records returned.
        set_config('maxrecords', 2, 'block_ucla_library_portal');

        $nodes = block_ucla_library_portal::get_navigation_nodes(
                array('course' => $course));
        $this->assertEquals(1, count($nodes));

        // Count how many records are returned.
        $moodleurl = $nodes[0]->action;
        parse_str($moodleurl->get_query_string(false), $results);
        $this->assertEquals(2, count($results));
    }
}
