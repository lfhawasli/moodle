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
 * Tests the ucla_reg_classinfo_cron class.
 *
 * @package    local_ucla
 * @category   test
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/ucla/cronlib.php');

/**
 * PHPunit testcase class.
 *
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group ucla
 * @group local_ucla
 */
class ucla_reg_classinfo_cron_testcase extends advanced_testcase {

    /**
     * Stores mocked version of ucla_reg_classinfo_cron.
     * @var ucla_reg_classinfo_cron
     */
    private $mockuclaregclassinfocron = null;

    /**
     * Used by mocked_query_registrar to return data for a given stored
     * procedure, term, and srs.
     * @var array
     */
    private $mockregdata = array();

    /**
     * Stubs the query_registrar method of ucla_reg_classinfo_cron class, so we
     * aren't actually making a live call to the Registrar.
     *
     * Must call set_mockregdata() beforehand to set what data should be
     * returned.
     *
     * @param string $sp        Stored procedure to call.
     * @param array $data       We are expecting an array with term/srs keys.
     *
     * @return array            Returns corresponding value in $mockregdata.
     */
    public function mocked_query_registrar($sp, $data) {
        /* The $mockregdata array is indexed as follows:
         *  [storedprocedure] => [term] => [srs] => [array of results]
         */
        @$retval = $this->mockregdata[$sp][$data['term']][$data['srs']];
        if (empty($retval)) {
            return false;
        } else {
            return array($retval);
        }
    }

    /**
     * Prepares data that will be returned by mocked_query_registrar.
     *
     * @param string $sp
     * @param string $term
     * @param string $srs
     * @param array $results
     */
    protected function set_mockregdata($sp, $term, $srs, array $results) {
        $this->mockregdata[$sp][$term][$srs] = $results;
    }

    /**
     * Create mocked class to stub out Registrar querying.
     */
    protected function setUp() {
        $this->resetAfterTest(true);

        // Create mocked version of the ucla_reg_classinfo_cron class.

        // Only stub the query_registrar method.
        $this->mockuclaregclassinfocron = $this->getMockBuilder('ucla_reg_classinfo_cron')
                ->setMethods(array('query_registrar'))
                ->getMock();

        // Method $this->mocked_query_registrar will be called instead of
        // ucla_reg_classinfo_cron->query_registrar.
        $this->mockuclaregclassinfocron->expects($this->any())
                ->method('query_registrar')
                ->will($this->returnCallback(array($this, 'mocked_query_registrar')));

        // Remove any previous registrar data.
        unset($this->mockregdata);
        $this->mockregdata = array();
    }

    /**
     * Test that records are inserted.
     */
    public function test_insert() {
        global $DB;

        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array());
        $course = array_pop($class);

        // Get entry from ucla_reg_classinfo and then delete it.
        $classinfo = $DB->get_record('ucla_reg_classinfo',
                array('term' => $course->term, 'srs' => $course->srs));
        $this->set_mockregdata('ccle_getclasses', $course->term, $course->srs,
                (array) $classinfo);

        $DB->delete_records('ucla_reg_classinfo',
                array('term' => $course->term, 'srs' => $course->srs));

        // Run cron and make sure it echos that no changes were made.
        ob_start();
        $result = $this->mockuclaregclassinfocron->run(array($course->term));
        $output = ob_get_clean();
        $this->assertEquals(true, $result);
        $this->assertContains('Inserted: 1', $output);
    }

    /**
     * Test that no records are updated.
     */
    public function test_no_update() {
        global $DB;

        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array());
        $course = array_pop($class);

        // Get entry from ucla_reg_classinfo, because we will use it to return
        // mock registrar data.
        $classinfo = $DB->get_record('ucla_reg_classinfo',
                array('term' => $course->term, 'srs' => $course->srs));
        $this->set_mockregdata('ccle_getclasses', $course->term, $course->srs,
                (array) $classinfo);

        // Run cron and make sure it echos that no changes were made.
        ob_start();
        $result = $this->mockuclaregclassinfocron->run(array($course->term));
        $output = ob_get_clean();
        $this->assertEquals(true, $result);
        $this->assertContains('No update needed: 1', $output);
    }

    /**
     * Test that we ignore records that no longer exist at the Registrar and
     * that they are marked as cancelled.
     */
    public function test_not_found() {
        global $DB;

        // Create course to be cancelled.
        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array());
        $cancelledcourse = array_pop($class);

        // Create another course that is normal. The cron needs at least 1 valid
        // course in order to proceed.
        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array('term' => $cancelledcourse->term));
        $course = array_pop($class);
        $classinfo = $DB->get_record('ucla_reg_classinfo',
                array('term' => $course->term, 'srs' => $course->srs));
        $this->set_mockregdata('ccle_getclasses', $course->term, $course->srs,
                (array) $classinfo);

        // Get entry from ucla_reg_classinfo and verify that course is not
        // cancelled.
        $classinfo = $DB->get_record('ucla_reg_classinfo',
                array('term' => $cancelledcourse->term, 'srs' => $cancelledcourse->srs));
        $this->assertNotEquals('X', $classinfo->enrolstat);

        // Run cron and make sure it echos that no changes were made.
        ob_start();
        $result = $this->mockuclaregclassinfocron->run(array($cancelledcourse->term));
        $output = ob_get_clean();
        $this->assertEquals(true, $result);
        $this->assertContains('Not found at registrar: 1', $output);

        // Verify that course is cancelled.
        $classinfo = $DB->get_record('ucla_reg_classinfo',
                array('term' => $cancelledcourse->term, 'srs' => $cancelledcourse->srs));
        $this->assertEquals('X', $classinfo->enrolstat);
    }

    /**
     * Test that records are updated.
     */
    public function test_update() {
        global $DB;

        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array());
        $course = array_pop($class);

        // Get entry from ucla_reg_classinfo and change some data.
        $classinfo = $DB->get_record('ucla_reg_classinfo',
                array('term' => $course->term, 'srs' => $course->srs));

        $classinfo->coursetitle = 'Changed course title';

        $this->set_mockregdata('ccle_getclasses', $course->term, $course->srs,
                (array) $classinfo);

        // Run cron and make sure it echos that no changes were made.
        ob_start();
        $result = $this->mockuclaregclassinfocron->run(array($course->term));
        $output = ob_get_clean();
        $this->assertEquals(true, $result);
        $this->assertContains('Updated: 1', $output);
    }

}
