<?php
// This file is part of the UCLA gradebook customizations plugin for Moodle - http://moodle.org/
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
 * Tests the MyUCLA gradebook webservice task for sending grades.
 *
 * @package    local_gradebook
 * @category   test
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/grade/lib.php');
require_once($CFG->dirroot . '/grade/report/grader/lib.php');

/**
 * PHPunit testcase class.
 *
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group ucla
 * @group local_gradebook
 */
class sendgrade_test extends advanced_testcase {

    /**
     * @var assign  Assignment module.
     */
    private $assign;

    /**
     * @var stdClass  Course record from the database.
     */
    private $course;

    /**
     * @var stdClass  User object from the database.
     */
    private $instructor;

    /**
     * @var \local_gradebook\task\mock_webservice
     */
    private $mockwebservice;

    /**
     * @var stdClass  User object from the database.
     */
    private $student;

    /**
     * @var resource    Temporary file created to track error_log calls.
     */
    private $tmplogfile;

    /**
     * Returns a version of send_myucla_grade_item that has its
     * get_webservice_client() remapped to the mocked_get_webservice_client()
     * method of the current class.
     *
     * @return \local_gradebook\task\send_myucla_grade
     */
    private function get_mock_myucla_task() {
        // Only stub the get_webservice_client method.
        $mockgradetask = $this->getMockBuilder('\local_gradebook\task\send_myucla_grade')
                ->setMethods(array('get_webservice_client'))
                ->getMock();

        // Method $this->mocked_get_webservice_client will be called instead of
        // send_myucla_grade_item->get_webservice_client().
        $mockgradetask->expects($this->any())
                ->method('get_webservice_client')
                ->will($this->returnCallback(array($this, 'mocked_get_webservice_client')));

        return $mockgradetask;
    }

    /**
     * Mocks the get_webservice_client() call in the send_myucla_grade_item
     * class.
     *
     * @return \local_gradebook\task\mock_webservice
     */
    public function mocked_get_webservice_client() {
        if (is_null($this->mockwebservice)) {
            $this->mockwebservice = new \local_gradebook\task\mock_webservice();
        }
        return $this->mockwebservice;
    }

    /**
     * Provides the actual error codes that the MUCLA moodleGradeModify
     * webservice returns in the moodleGradeModifyResult object.
     *
     * Note that we will need to replace the placeholder variables in the string
     * with the actual id.
     *
     * @return array
     */
    public function provider_myucla_webservice_error() {
        return array(
            array('Moodle Instance Verify failed'),
            array('ItemID <itemid> does not exist'),
            array('GradeID <gradeid> failed to be processed')
        );
    }

    /**
     * Sets the error_log output to be a temporary file.
     *
     * @return string
     */
    private function set_error_log() {
        $this->tmplogfile = tmpfile();
        $metadata = stream_get_meta_data($this->tmplogfile);
        $tmplogfilename = $metadata['uri'];
        ini_set('error_log', $tmplogfilename);

        return $tmplogfilename;
    }

    /**
     * Sets given grade for precreated assignment and student.
     *
     * @param float $grade
     * @return grade_grade  Returns grade_grade object that should be created.
     */
    protected function set_grade($grade) {
        // We are saving grades via the grader report, since the code for it is
        // more straightforward. See /grade/tests/report_graderlib_test.php.
        $gpr = new grade_plugin_return(array('type' => 'report',
            'plugin' => 'grader', 'courseid' => $this->course->id));
        $report = new grade_report_grader($this->course->id, $gpr,
                context_course::instance($this->course->id));

        $gradeitem  = grade_item::fetch(array('itemtype'     => 'mod',
                                              'itemmodule'   => 'assign',
                                              'iteminstance' => $this->assign->id,
                                              'courseid'     => $this->course->id));

        $data = new stdClass();
        $data->id = $this->course->id;
        $data->report = 'grader';
        $data->grade = array();
        $data->grade[$this->student->id] = array();
        $data->grade[$this->student->id][$gradeitem->id] = $grade;
        $data->timepageload = time(); 

        $warnings = $report->process_data($data);
        $this->assertEquals(count($warnings), 0);

        $gradeobj = grade_grade::fetch(array('itemid' => $gradeitem->id,
                                             'userid' => $this->student->id));
        $this->assertNotEmpty($gradeobj);
        $gradeobj->load_grade_item();  // Will need grade item info later.

        return $gradeobj;
    }

    /**
     * Creates test course, instructor, and student.
     */
    protected function setUp() {
        $this->resetAfterTest(true);

        // Create course.
        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array());
        $course = array_pop($class);
        $this->course = get_course($course->courseid);

        // Create instructor and student.
        $roles = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_ucla_roles(array('editinginstructor', 'student'));
        $this->instructor = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_user();
        $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->enrol_reg_user($this->instructor->id,
                                 $this->course->id,
                                 $roles['editinginstructor']);
        $this->student = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_user();
        $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->enrol_reg_user($this->student->id,
                             $this->course->id,
                             $roles['student']);

        // Set instructor as the person modifying grades.
        $this->setUser($this->instructor);

        // Create graded activity, no adhoc tasks should be created, because
        // $CFG->gradebook_send_updates is not set yet.
        $this->assign = $this->getDataGenerator()
                ->create_module('assign', array('course' => $this->course->id));

        // Set MyUCLA gradebook settings.
        set_config('gradebook_id', 99);
        set_config('gradebook_password', 'test');
        set_config('gradebook_send_updates', 1);

        // Make sure the adhoc queue is cleared.
        while ($task = \core\task\manager::get_next_adhoc_task(time())) {
            \core\task\manager::adhoc_task_complete($task);
        }
    }

    /**
     * Clean up temporary php files after each test.
     */
    public function tearDown() {
        if (is_resource($this->tmplogfile)) {
            // Closing this file handler will delete temporary file.
            fclose($this->tmplogfile);
            unset($this->tmplogfile);
        }
    }

    /**
     * Test that MyUCLA error codes are handled properly.
     *
     * @dataProvider provider_myucla_webservice_error
     * @param string $errormsg
     */
    public function test_execute_myucla_errors($errormsg) {
        $grade = $this->set_grade(89.99);

        // Need to replace placeholders in error message.
        $errormsg = str_replace('<itemid>', $grade->grade_item->id, $errormsg);
        $errormsg = str_replace('<gradeid>', $grade->id, $errormsg);

        // Create task.
        $task = $this->get_mock_myucla_task();
        $result = $task->set_gradeinfo($grade);
        $this->assertTrue($result);

        // Make webservice return MyUCLA status message with error.
        $returnresult = new \stdClass();
        $returnresult->status = 0;
        $returnresult->message = $errormsg;
        $this->mocked_get_webservice_client()
                ->returnresult
                ->moodleGradeModifyResult = $returnresult;

        // Make sure that errors are logged.
        $outputfilename = $this->set_error_log();

        // Execute task, should throw an exception.
        $exceptionthrown = false;
        try {
            $task->execute();
        } catch (\Exception $e) {
            $exceptionthrown = true;
        }
        $this->assertTrue($exceptionthrown);

        // Now read from the file and make sure it contains a error message.
        $output = file_get_contents($outputfilename);
        $this->assertNotEmpty(strpos($output,
                'ERROR: Exception sending data to moodleGradeModify webservice: '
                . $errormsg));
    }

    /**
     * Test that SoapFault exceptions are handled properly.
     */
    public function test_execute_soapfault() {
        $grade = $this->set_grade(89.99);

        // Create task.
        $task = $this->get_mock_myucla_task();
        $result = $task->set_gradeinfo($grade);
        $this->assertTrue($result);

        // Make webservice throw an exception.
        $this->mocked_get_webservice_client()->thrownexception =
                new \SoapFault('code', 'message');

        // Make sure that errors are logged.
        $outputfilename = $this->set_error_log();

        // Execute task, should throw an exception.
        $exceptionthrown = false;
        try {
            $task->execute();
        } catch (\SoapFault $e) {
            $exceptionthrown = true;
        }
        $this->assertTrue($exceptionthrown);

        // Now read from the file and make sure it contains a error message.
        $output = file_get_contents($outputfilename);
        $this->assertNotEmpty(strpos($output,
                'ERROR: SoapFault sending data to moodleGradeModify webservice: [code] message'));

    }

    /**
     * Do a simple test to make sure that parameters were sent.
     */
    public function test_execute_success() {
        $grade = $this->set_grade(89.99);

        // Create task.
        $task = $this->get_mock_myucla_task();
        $result = $task->set_gradeinfo($grade);
        $this->assertTrue($result);

        // Execute task.
        $task->execute();

        // Make sure that parameters were sent.
        $this->assertNotEmpty($this->mockwebservice->lastparams);
    }

    /**
     * Ensures that format_myucla_parameters() returns the proper parameters.
     */
    public function test_format_myucla_parameters() {
        global $CFG;

        // Should start with nothing to do.
        $task = \core\task\manager::get_next_adhoc_task(time());
        $this->assertNull($task);

        // Now give student a grade.
        $grade = $this->set_grade(100.00);

        // A grade_grade object should have been generated and an adhoc
        // task of type send_myucla_grade should have been created.
        $task = \core\task\manager::get_next_adhoc_task(time());
        $this->assertNotNull($task);
        $this->assertEquals('local_gradebook\task\send_myucla_grade',
                get_class($task));
        // Important to call this to release the cron lock.
        \core\task\manager::adhoc_task_complete($task);

        // Get course that format_myucla_parameters needs.
        $courses = $task->get_courses_info();
        // Shouldn't be a cross-listed course.
        $this->assertEquals(1, count($courses));
        $course = reset($courses);  // Get first entry.

        // Make sure the task has the proper elements set.
        $myuclaparams = $task->format_myucla_parameters($course);

        // Verify that mInstance is the same.
        $this->assertEquals($CFG->gradebook_id, $myuclaparams['mInstance']['miID']);
        $this->assertEquals($CFG->gradebook_password, $myuclaparams['mInstance']['miPassword']);

        // Verify that mGrade is the same.
        $this->assertEquals($grade->id, $myuclaparams['mGrade']['gradeID']);
        $this->assertEquals($grade->grade_item->id, $myuclaparams['mGrade']['itemID']);
        $this->assertEquals($course->term, $myuclaparams['mGrade']['term']);
        $this->assertEquals($course->subj_area, $myuclaparams['mGrade']['subjectArea']);
        $this->assertEquals($course->crsidx, $myuclaparams['mGrade']['catalogNumber']);
        $this->assertEquals($course->secidx, $myuclaparams['mGrade']['sectionNumber']);
        $this->assertEquals($course->srs, $myuclaparams['mGrade']['srs']);
        $this->assertEquals($course->uidstudent, $myuclaparams['mGrade']['uidStudent']);
        $this->assertEquals($grade->finalgrade, $myuclaparams['mGrade']['viewableGrade']);
        $this->assertEmpty($myuclaparams['mGrade']['comment']);
        $this->assertFalse($myuclaparams['mGrade']['excused']);

        // Verify that the mTransaction info is the same.
        $this->assertEquals($this->instructor->idnumber, $myuclaparams['mTransaction']['userUID']);
        $this->assertEquals(fullname($this->instructor), $myuclaparams['mTransaction']['userName']);
    }

    /**
     * Create a grade_item and make sure that an ad-hoc task is created.
     */
    public function test_task_added() {
        // Should start with nothing to do.
        $task = \core\task\manager::get_next_adhoc_task(time());
        $this->assertNull($task);

        // Now give student a grade.
        $grade = $this->set_grade(100.00);

        // A grade_grade object should have been generated and an adhoc
        // task of type send_myucla_grade should have been created.
        $task = \core\task\manager::get_next_adhoc_task(time());
        $this->assertNotNull($task);
        $this->assertEquals('local_gradebook\task\send_myucla_grade',
                get_class($task));
        // Important to call this to release the cron lock.
        \core\task\manager::adhoc_task_complete($task);

        // Make sure the task has the proper elements set.
        $gradeinfo  = $task->get_custom_data();

        // Verify that grade item info is the same.
        $this->assertEquals($grade->id, $gradeinfo->id);
        $this->assertEquals($grade->grade_item->courseid, $gradeinfo->courseid);
        $this->assertEquals($grade->grade_item->id, $gradeinfo->itemid);
        $this->assertEquals($grade->grade_item->itemtype, $gradeinfo->itemtype);
        $this->assertEquals(100.00, $gradeinfo->finalgrade);    // We set this earlier.
        $this->assertEquals($grade->excluded, $gradeinfo->excluded);
        $this->assertEquals($grade->feedback, $gradeinfo->comment);

        // Verify that the transaction user is the same.
        $transactioninfo = $gradeinfo->transactioninfo;
        $this->assertEquals($this->instructor->idnumber, $transactioninfo->idnumber);
        $this->assertEquals(fullname($this->instructor), $transactioninfo->name);
    }
}
