<?php
// This file is part of the UCLA stats console plugin for Moodle - http://moodle.org/
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
 * Unit tests for UCLA stats subject_area_report class.
 *
 * @package    report_uclastats
 * @category   test
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/report/uclastats/reports/subject_area_report.php');

/**
 * PHPunit testcase class.
 *
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group ucla
 * @group report_uclastats
 */
class subject_area_report_test extends advanced_testcase {

    /**
     * Used to store the course ids that were created.
     * @var array
     */
    protected $courseids = array();

    /**
     * Report object.
     * @var sites_per_term
     */
    protected $report = null;

    /**
     * Creates a mix of cross-listed and non-crosslisted courses both in same
     * term and subject area.
     */
    protected function create_courses() {
        unset($this->courses);

        // Non-crosslisted course.
        $param = array('term' => '12S',
                       'subj_area' => 'NR EAST',
                       'division' => 'HU');
        $results = $this->getDataGenerator()
                        ->get_plugin_generator('local_ucla')
                        ->create_class($param);
        $this->courseids['noncrosslisted'] = array_pop($results)->courseid;

        // Crosslisted course.
        $param = array(
            array('term' => '12S', 'srs' => '285061200',
                'subj_area' => 'NR EAST', 'crsidx' => '0020  M ',
                'secidx' => ' 001  ', 'division' => 'HU'),
            array('term' => '12S', 'srs' => '257060200',
                'subj_area' => 'ASIAN', 'crsidx' => '0020  M ',
                'secidx' => ' 001  ', 'division' => 'HU'),
            array('term' => '12S', 'srs' => '334060200',
                'subj_area' => 'SLAVIC', 'crsidx' => '0020  M ',
                'secidx' => ' 001  ', 'division' => 'HU'));

        $results = $this->getDataGenerator()
                        ->get_plugin_generator('local_ucla')
                        ->create_class($param);
        $this->courseids['crosslisted'] = array_pop($results)->courseid;
    }

    /**
     * Helper function to run report and return results.
     *
     * @return array
     */
    private function run_report() {
        $resultid = $this->report->run(array('term' => '12S',
                                             'subjarea' => 'NR EAST'));
        $result = $this->report->get_results($resultid);
        return $result->results;
    }

    /**
     * Creates test courses.
     */
    public function setUp() {
        $this->resetAfterTest(true);
        $this->create_courses();

        // Stub get_term_info() method, because we don't have access to registrar.
        $stub = $this->getMockBuilder('subject_area_report')
                     ->setConstructorArgs(array(get_admin()))
                     ->setMethods(array('get_term_info'))
                     ->getMock();

        // For future tests can set these to real values.
        $stub->expects($this->any())
             ->method('get_term_info')
             ->will($this->returnValue(array('start' => time(), 'end' => time())));

        $this->report = $stub;
    }

    /**
     * Test query_quizzes by running report with and without quizzes created
     * for non-crosslisted and crosslisted courses.
     */
    public function test_query_quizzes() {
        // Run test for different term. Make sure that results are stored for
        // each run.
        $results = $this->run_report();
        $this->assertEquals(0,
                $results[$this->courseids['noncrosslisted']]['course_quizzes']);
        $this->assertEquals(0,
                $results[$this->courseids['crosslisted']]['course_quizzes']);

        // Create 1 quiz for both courses.
        foreach ($this->courseids as $courseid) {
            $this->getDataGenerator()
                    ->get_plugin_generator('mod_quiz')
                    ->create_instance(array('course'=>$courseid));
        }

        $results = $this->run_report();        
        $this->assertEquals(1,
                $results[$this->courseids['noncrosslisted']]['course_quizzes']);
        $this->assertEquals(1,
                $results[$this->courseids['crosslisted']]['course_quizzes']);

        // Let's add another quiz.
        foreach ($this->courseids as $courseid) {
            $this->getDataGenerator()
                    ->get_plugin_generator('mod_quiz')
                    ->create_instance(array('course'=>$courseid));
        }
        $results = $this->run_report();
        $this->assertEquals(2,
                $results[$this->courseids['noncrosslisted']]['course_quizzes']);
        $this->assertEquals(2,
                $results[$this->courseids['crosslisted']]['course_quizzes']);
    }

}
