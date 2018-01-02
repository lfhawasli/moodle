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
 * Tests the MyUCLA gradebook webservice task for sending grade items.
 *
 * @package    local_gradebook
 * @category   test
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * PHPunit testcase class.
 *
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group ucla
 * @group local_gradebook
 */
class sendgradeitem_test extends advanced_testcase {

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
     * @var resource    Temporary file created to track error_log calls.
     */
    private $tmplogfile;

    /**
     * Helper function to get the course module id from the grade item.
     * @param grade_item $gradeitem
     * @return int
     */
    private function get_cmid_from_gradeitem(grade_item $gradeitem) {
        global $DB;
        $sql = "SELECT cm.id
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id=cm.module
                 WHERE cm.course=:courseid
                   AND m.name=:module
                   AND cm.instance=:instance";
        return $DB->get_field_sql($sql, array('courseid' => $gradeitem->courseid,
            'module' => $gradeitem->itemmodule,
            'instance' => $gradeitem->iteminstance));
    }

    /**
     * Returns a version of send_myucla_grade_item that has its
     * get_webservice_client() remapped to the mocked_get_webservice_client()
     * method of the current class.
     *
     * @return \local_gradebook\task\send_myucla_grade_item
     */
    private function get_mock_myucla_task() {
        // Only stub the query_registrar method.
        $mockgradeitemtask = $this->getMockBuilder('\local_gradebook\task\send_myucla_grade_item')
                ->setMethods(array('get_webservice_client'))
                ->getMock();

        // Method $this->mocked_get_webservice_client will be called instead of
        // send_myucla_grade_item->get_webservice_client().
        $mockgradeitemtask->expects($this->any())
                ->method('get_webservice_client')
                ->will($this->returnCallback(array($this, 'mocked_get_webservice_client')));

        return $mockgradeitemtask;
    }

    /**
     * Helper function to get send_myucla_grade_item task.
     *
     * @return send_myucla_grade_item
     */
    private function get_send_myucla_grade_item_task() {
        // A grade item should have been automatically generated and an adhoc
        // task of type send_myucla_grade_item should have been created.
        $task = \core\task\manager::get_next_adhoc_task(time());
        $this->assertNotNull($task);
        $this->assertEquals('local_gradebook\task\send_myucla_grade_item',
                get_class($task));

        // Important to call this to release the cron lock.
        \core\task\manager::adhoc_task_complete($task);

        // Make sure the adhoc queue is cleared.
        while ($extratask = \core\task\manager::get_next_adhoc_task(time())) {
            \core\task\manager::adhoc_task_complete($extratask);
        }

        return $task;
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
     * Provides the actual error codes that the MUCLA moodleItemModify
     * webservice returns in the moodleItemModifyResult object.
     *
     * @return array
     */
    public function provider_myucla_webservice_error() {
        return array(
            array('Moodle Instance Verify failed'),
            array('Class list is empty'),
            array('Failed to process itemID'),
            array('Failed to process the following class id(s)'),
            array(' Failed to process the request')
        );
    }

    /**
     * Creates test course and instructor.
     */
    protected function setUp() {
        $this->resetAfterTest(true);

        // Create course.
        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array());
        $course = array_pop($class);
        $this->course = get_course($course->courseid);

        // Create instructor.
        $roles = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_ucla_roles(array('editinginstructor'));
        $this->instructor = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_user();
        $this->getDataGenerator()
                ->enrol_user($this->instructor->id,
                             $this->course->id,
                             $roles['editinginstructor']);

        // Set instructor as the person modifying grade items.
        $this->setUser($this->instructor);

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
        // Create graded activity and get grade item.
        $assign = $this->getDataGenerator()
                ->create_module('assign', array('course' => $this->course->id));
        $gradeitem = grade_item::fetch(array('itemtype'     => 'mod',
                                             'itemmodule'   => 'assign',
                                             'iteminstance' => $assign->id,
                                             'courseid'     => $this->course->id));

        // Create task.
        $task = $this->get_mock_myucla_task();
        $result = $task->set_gradeinfo($gradeitem);
        $this->assertTrue($result);

        // Make webservice return MyUCLA status message with error.
        $returnresult = new \stdClass();
        $returnresult->status = 0;
        $returnresult->message = $errormsg;
        $this->mocked_get_webservice_client()
                ->returnresult
                ->moodleItemModifyResult = $returnresult;

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
                'ERROR: Exception sending data to moodleItemModify webservice: '
                . $errormsg));
    }

    /**
     * Test that SoapFault exceptions are handled properly.
     */
    public function test_execute_soapfault() {
        // Create graded activity and get grade item.
        $assign = $this->getDataGenerator()
                ->create_module('assign', array('course' => $this->course->id));
        $gradeitem = grade_item::fetch(array('itemtype'     => 'mod',
                                             'itemmodule'   => 'assign',
                                             'iteminstance' => $assign->id,
                                             'courseid'     => $this->course->id));

        // Create task.
        $task = $this->get_mock_myucla_task();
        $result = $task->set_gradeinfo($gradeitem);
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
                'ERROR: SoapFault sending data to moodleItemModify webservice: [code] message'));
    }

    /**
     * Do a simple test to make sure that parameters were sent.
     */
    public function test_execute_success() {
        global $CFG;

        // Create graded activity and get grade item.
        $assign = $this->getDataGenerator()
                ->create_module('assign', array('course' => $this->course->id));
        $gradeitem = grade_item::fetch(array('itemtype'     => 'mod',
                                             'itemmodule'   => 'assign',
                                             'iteminstance' => $assign->id,
                                             'courseid'     => $this->course->id));

        // Create task.
        $task = $this->get_mock_myucla_task();
        $result = $task->set_gradeinfo($gradeitem);
        $this->assertTrue($result);

        // Make sure success was logged.
        $CFG->gradebook_log_success = 1;
        $outputfilename = $this->set_error_log();

        // Execute task. Use output buffering to capture output.
        $task->execute();

        // Now read from the file and make sure it contains a success message.
        $output = file_get_contents($outputfilename);
        $this->assertNotEmpty(strpos($output,
                'SUCCESS: Send data to moodleItemModify webservice'));

        // Make sure that parameters were sent.
        $this->assertNotEmpty($this->mockwebservice->lastparams);
    }

    /**
     * Ensures that format_myucla_parameters() returns the proper parameters.
     */
    public function test_format_myucla_parameters() {
        global $CFG;

        // Create graded activity due 1 week from now.
        $assign = $this->getDataGenerator()
                ->create_module('assign', array('course' => $this->course->id,
                    'duedate' => strtotime("+1 week")));

        $task = $this->get_send_myucla_grade_item_task();

        // Get course that format_myucla_parameters needs.
        $courses = $task->get_courses_info();
        // Shouldn't be a cross-listed course.
        $this->assertEquals(1, count($courses));
        $course = reset($courses);  // Get first entry.

        // Make sure the task has the proper elements set.
        $myuclaparams = $task->format_myucla_parameters($course);
        $gradeitem = grade_item::fetch(array('itemtype'     => 'mod',
                                             'itemmodule'   => 'assign',
                                             'iteminstance' => $assign->id,
                                             'courseid'     => $this->course->id));

        // Verify that mInstance is the same.
        $this->assertEquals($CFG->gradebook_id, $myuclaparams['mInstance']['miID']);
        $this->assertEquals($CFG->gradebook_password, $myuclaparams['mInstance']['miPassword']);

        // Verify that mItem is the same.
        $this->assertEquals($gradeitem->id, $myuclaparams['mItem']['itemID']);
        $this->assertEquals($gradeitem->itemname, $myuclaparams['mItem']['itemName']);
        $this->assertEquals($gradeitem->categoryid, $myuclaparams['mItem']['categoryID']);
        $this->assertEmpty($myuclaparams['mItem']['categoryName']);
        $this->assertEquals(!($gradeitem->hidden), $myuclaparams['mItem']['itemReleaseScores']);
        $this->assertTrue(validateUrlSyntax($myuclaparams['mItem']['itemURL']));
        $this->assertTrue(validateUrlSyntax($myuclaparams['mItem']['itemEditURL']));
        $this->assertEmpty($myuclaparams['mItem']['itemComment']);
        $this->assertEquals($gradeitem->grademax, $myuclaparams['mItem']['itemMaxScore']);

        // Make sure itemEditURL is correct.
        $cmid = $this->get_cmid_from_gradeitem($gradeitem);
        $this->assertEquals($CFG->wwwroot . '/mod/assign/view.php?id=' . $cmid,
                $myuclaparams['mItem']['itemEditURL']);

        // Need to make sure that itemDue is send in yyyy-mm-dd hh:mm:ss.fff,
        // where fff stands for milliseconds, format.
        $expecteddatetime = date('Y-m-d H:i:s.000', $assign->duedate);
        $this->assertEquals($expecteddatetime, $myuclaparams['mItem']['itemDue']);

        // Verify that the mClassList info is the same.
        $this->assertEquals($course->term, $myuclaparams['mClassList'][0]['term']);
        $this->assertEquals($course->subj_area, $myuclaparams['mClassList'][0]['subjectArea']);
        $this->assertEquals($course->crsidx, $myuclaparams['mClassList'][0]['catalogNumber']);
        $this->assertEquals($course->secidx, $myuclaparams['mClassList'][0]['sectionNumber']);
        $this->assertEquals($course->srs, $myuclaparams['mClassList'][0]['srs']);

        // Verify that the mTransaction info is the same.
        $this->assertEquals($this->instructor->idnumber, $myuclaparams['mTransaction']['userUID']);
        $this->assertEquals(fullname($this->instructor), $myuclaparams['mTransaction']['userName']);
    }

    /**
     * The test_format_myucla_parameters tests the assign module which does not
     * have a grade.php. We want to test with the lesson module that does. As
     * well as test manual grade items to make sure we get the right URL sent.
     */
    public function test_get_editurl() {
        global $CFG;

        // Create lesson.
        $lesson = $this->getDataGenerator()->create_module('lesson',
                array('course' => $this->course));

        $task = $this->get_send_myucla_grade_item_task();

        // Get course that format_myucla_parameters needs.
        $courses = $task->get_courses_info();
        // Shouldn't be a cross-listed course.
        $this->assertEquals(1, count($courses));
        $course = reset($courses);  // Get first entry.

        // Make sure the task has the proper elements set.
        $myuclaparams = $task->format_myucla_parameters($course);
        $lessonitem = grade_item::fetch(array('itemtype'     => 'mod',
                                             'itemmodule'   => 'lesson',
                                             'iteminstance' => $lesson->id,
                                             'courseid'     => $this->course->id));

        // Make sure itemEditURL is correct.
        $cmid = $this->get_cmid_from_gradeitem($lessonitem);
        $this->assertEquals($CFG->wwwroot . '/mod/lesson/grade.php?id=' . $cmid .
                '&itemnumber=' . $lessonitem->itemnumber,
                $myuclaparams['mItem']['itemEditURL']);

        // Create manual grade item.
        $coursecategory = grade_category::fetch_course_category($this->course->id);
        $itemparams = array(
            'courseid' => $this->course->id,
            'itemtype' => 'manual',
            'itemname' => 'Midterm',
            'categoryid' => $coursecategory->id,
        );

        $manualitem = new \grade_item($itemparams, false);
        $itemid = $manualitem->insert('manual');

        $task = $this->get_send_myucla_grade_item_task();
        $myuclaparams = $task->format_myucla_parameters($course);

        // Make sure name is the same since item is not a module.
        $this->assertEquals($manualitem->itemname, $myuclaparams['mItem']['itemName']);
        $this->assertEquals($CFG->wwwroot . '/grade/report/singleview/index.php?id=' .
                $this->course->id . '&itemid=' . $itemid . '&item=grade',
                $myuclaparams['mItem']['itemEditURL']);
    }

    /**
     * Create a grade_item and make sure that an ad-hoc task is created.
     */
    public function test_task_added() {
        // Should start with nothing to do.
        $task = \core\task\manager::get_next_adhoc_task(time());
        $this->assertNull($task);

        // Create graded activity due 1 week from now.
        $assign = $this->getDataGenerator()
                ->create_module('assign', array('course' => $this->course->id));

        // A grade item should have been automatically generated and an adhoc
        // task of type send_myucla_grade_item should have been created.
        $task = \core\task\manager::get_next_adhoc_task(time());
        $this->assertNotNull($task);
        $this->assertEquals('local_gradebook\task\send_myucla_grade_item',
                get_class($task));
        // Important to call this to release the cron lock.
        \core\task\manager::adhoc_task_complete($task);

        // Make sure the task has the proper elements set.
        $gradeinfo = $task->get_custom_data();
        $gradeitem = grade_item::fetch(array('itemtype'     => 'mod',
                                             'itemmodule'   => 'assign',
                                             'iteminstance' => $assign->id,
                                             'courseid'     => $this->course->id));

        // Verify that grade item info is the same.
        $this->assertEquals($gradeitem->id, $gradeinfo->id);
        $this->assertEquals($gradeitem->itemname, $gradeinfo->itemname);
        $this->assertEquals($gradeitem->categoryid, $gradeinfo->categoryid);
        $this->assertEquals($gradeitem->is_hidden(), $gradeinfo->hidden);
        $this->assertEquals($gradeitem->grademax, $gradeinfo->grademax);

        // Verify that the transaction user is the same.
        $transactioninfo = $gradeinfo->transactioninfo;
        $this->assertEquals($this->instructor->idnumber, $transactioninfo->idnumber);
        $this->assertEquals(fullname($this->instructor), $transactioninfo->name);
    }
}
