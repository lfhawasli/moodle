<?php
// This file is part of the UCLA course download plugin for Moodle - http://moodle.org/
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
 * Testcase file.
 *
 * @package     block_ucla_course_download
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

// Include class we are testing.
global $CFG;
require_once($CFG->dirroot . '/blocks/ucla_course_download/classes/base.php');
require_once($CFG->dirroot . '/blocks/ucla_course_download/classes/files.php');
require_once("$CFG->dirroot/blocks/moodleblock.class.php");
require_once("$CFG->dirroot/blocks/ucla_course_download/block_ucla_course_download.php");

/**
 * Tests for the block_ucla_course_download_files class.
 *
 * @package    block_ucla_course_download
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_course_download_files_test extends advanced_testcase {
    /**
     * Test course.
     *
     * @var object
     */
    private $course = null;

    /**
     * Message sink for emails.
     *
     * @var phpunit_message_sink
     */
    private $sink = null;

    /**
     * Test student.
     *
     * @var object
     */
    private $student = null;

    /**
     * Test teacher.
     *
     * @var object
     */
    private $teacher = null;

    /**
     * Helper method to compare the results from test method populate_course and
     * the results from block_ucla_course_download_files method build_zip_array.
     *
     * @param array $expected   Results from populate_course.
     * @param array $actual     Results from build_zip_array.
     */
    private function compare_content($expected, $actual) {
        // Make sure that everything from $expected is in $actual.
        foreach ($expected as $expectedfile) {
            $this->assertArrayHasKey($expectedfile, $actual);
        }

        // Make sure that everything from $actual is in $expected.
        foreach ($actual as $filepath => $storedfile) {
            $this->assertTrue(in_array($filepath, $expected));
        }
    }

    /**
     * Helper method to populate a course with a specified number of files and
     * options.
     *
     * @param array $contents   An array of arrays that will be used as the
     *                          $options parameter for mod_resource generator.
     * @return array            Will return the <sectionname>/<filename> created
     *                          for the corresponding index in the passed in
     *                          $content array.
     */
    private function populate_course(array $contents) {
        // Need to be logged in as someone with ability to add content.
        $this->setUser($this->teacher);

        // Get resource generator. It will be able to create actual files.
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_resource');

        // The course should already have all its sections created.
        $format = course_get_format($this->course);

        $retval = array();  // Used to assert files are queried properly.
        $fs = get_file_storage();
        foreach ($contents as $index => $options) {
            $resource = $generator->create_instance(array('course' => $this->course->id), $options);
            $cm = get_coursemodule_from_instance('resource', $resource->id);
            // Generator creates files named like this.
            $sectionnum = 0;
            if (isset($options['section'])) {
                $sectionnum = $options['section'];
            }
            $sectionname = $format->get_section_name($sectionnum);

            // Get filename.
            $context = context_module::instance($cm->id);
            $files = $fs->get_area_files($context->id, 'mod_resource', 'content', false, '', false);
            $file = reset($files);

            $retval[$index] = $sectionname . '/' . $file->get_filename();
        }

        return $retval;
    }

    /**
     * Sets up test course and users.
     */
    public function setUp() {
        global $DB;
        $this->resetAfterTest(true);

        // Redirect emails to message sink.
        unset_config('noemailever');
        $this->sink = $this->redirectEmails();

        // Create test course and users.
        $this->course = $this->getDataGenerator()->create_course();
        $this->student = $this->getDataGenerator()->create_user();
        $this->teacher = $this->getDataGenerator()->create_user();

        // Get student and teacher roleids.
        $studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $teacherroleid = $DB->get_field('role', 'id', array('shortname' => 'editingteacher'));

        // Enroll users.
        $this->getDataGenerator()->enrol_user($this->student->id,
                $this->course->id, $studentroleid);
        $this->getDataGenerator()->enrol_user($this->teacher->id,
                $this->course->id, $teacherroleid);
    }

    /**
     * Make sure that temporary zip files in temp folder are deleted.
     *
     * @group totest
     */
    public function test_cleanup() {
        global $CFG;

        // Make sure there is nothing in the temp directory.
        if (file_exists($CFG->tempdir.'/coursedownloaddir')) {
            $this->assertEquals(2, count(scandir($CFG->tempdir.'/coursedownloaddir')));
        }

        // Add content to section 5.
        $contenttocreate[] = array('section' => 5);
        $expectedfiles = $this->populate_course($contenttocreate);

        $coursedownload = new block_ucla_course_download_files(
                $this->course->id, $this->student->id);
        $coursedownload->add_request();
        $request = $coursedownload->process_request();
        $this->assertCount(1, $this->sink->get_messages());

        // Make sure there is still nothing in the temp directory.
        $this->assertEquals(2, count(scandir($CFG->tempdir.'/coursedownloaddir')));
    }

    /**
     * Tests that old requests are deleted.
     */
    public function test_delete_old_requests() {
        global $DB;
        
        // Set ziplifetime to a known value (7 days).
        set_config('ziplifetime', 7, 'block_ucla_course_download');

        // Add content and create initial zip.
        $contenttocreate[] = array('section' => 1);
        $this->populate_course($contenttocreate);
        $coursefiles = new block_ucla_course_download_files(
                $this->course->id, $this->teacher->id);
        $coursefiles->add_request();
        $request = $coursefiles->process_request();
        $this->assertCount(1, $this->sink->get_messages());
        $this->assertNotEmpty($request->fileid);
        $this->assertEquals('request_completed', $coursefiles->get_request_status());

        // Now, make request really old (7 days + 1 second).
        $request->timerequested = $request->timerequested - 7*DAYSECS - 1;
        $DB->update_record('ucla_archives', $request);
        $coursefiles->refresh();

        // Process request again, file should be deleted.
        $request = $coursefiles->process_request();
        $this->assertNull($request);
        $this->assertEquals('request_available', $coursefiles->get_request_status());

        // Make sure that existing request was made inactive.
        $request = $coursefiles->get_request();
        $this->assertEmpty($request);
        $request = $coursefiles->get_request(0);
        $this->assertNotEmpty($request);
    }

    /**
     * Tests that emails are formatted properly.
     */
    public function test_email() {
        // Add content to section 5.
        $contenttocreate[] = array('section' => 5);
        $this->populate_course($contenttocreate);

        // Declare variables for email strings.
        $a = new StdClass();
        $a->shortname = $this->course->shortname;
        $a->ziplifetime = get_config('block_ucla_course_download', 'ziplifetime');
        $url = new moodle_url('/blocks/ucla_course_download/view.php',
                array('courseid' => $this->course->id));
        $a->url = $url->out();

        // Check email format for both student and teacher roles.
        foreach (array('student', 'teacher') as $user) {
            $coursedownload = new block_ucla_course_download_files(
                    $this->course->id, $this->$user->id);
            $coursedownload->add_request();
            $coursedownload->process_request();
            $a->type = $coursedownload->get_type();
            // Get email.
            $emails = $this->sink->get_messages();
            $this->assertCount(1, $emails);
            $this->sink->clear();

            // Verify email contents - subject and body.
            $this->assertEquals(get_string('emailsubject', 'block_ucla_course_download', $a), $emails[0]->subject);
            $expectedbody = get_string('emailmessage', 'block_ucla_course_download', $a);
            // A teacher should not recieve a copyright warning, but a student should.
            if ($user != 'teacher') {
                $expectedbody .= "\n\n" . get_string('emailcopyright', 'block_ucla_course_download');
            }
            // Compare email body, accounting for formatting added when it's sent.
            $this->assertEquals(preg_replace('/\n/', ' ', $expectedbody),
                    trim(preg_replace('/\n/', ' ', $emails[0]->body)));
        }
    }

    /**
     * Test that an empty course will not generate a zip file.
     */
    public function test_empty_course() {
        // Test as a student and then teacher. Should both be the same.
        foreach (array('student', 'teacher') as $user) {
            $coursefiles = new block_ucla_course_download_files(
                    $this->course->id, $this->$user->id);

            // Shouldn't be able to make a request.
            $status = $coursefiles->get_request_status();
            $this->assertEquals('request_unavailable', $status);

            // But add request anyways and make sure class doesn't blow up.
            $result = $coursefiles->add_request();
            $this->assertTrue($result);

            $request = $coursefiles->get_request();
            $this->assertNotEmpty($request);

            // Request should then be deleted.
            $processedrequest = $coursefiles->process_request();
            $this->assertCount(0, $this->sink->get_messages());
            $this->assertNull($processedrequest);
            $request = $coursefiles->get_request();
            $this->assertEmpty($request);
        }
    }

    /**
     * Test creating a zip file for a course with a lot of content.
     */
    public function test_filled_course() {
        // Create as many number of file resources as the section number + 1.
        $contenttocreate = array();
        $numsections = course_get_format($this->course)->get_last_section_number();
        for ($section=0; $section<=$numsections; $section++) {
            // Plus 1, because we want something in "Site info".
            $numfiles = $section + 1;
            for ($i=0; $i<$numfiles; $i++) {
                $contenttocreate[] = array('section' => $section);
            }
        }
        $expectedfiles = $this->populate_course($contenttocreate);

        // Course should have lots of content now. Create and process request.
        $coursefiles = new block_ucla_course_download_files(
                $this->course->id, $this->teacher->id);
        
        $result = $coursefiles->add_request();
        $this->assertTrue($result);
        $request = $coursefiles->get_request();
        // Clone, since process requests modifies the request variable.
        $initialrequest = clone $request;
        $processedrequest = $coursefiles->process_request();
        $this->assertCount(1, $this->sink->get_messages());

        // Newly processed request should have proper fields set.
        $this->assertEquals('request_completed', $coursefiles->get_request_status());
        foreach (array('fileid', 'timeupdated') as $column) {
            $this->assertNotEquals($initialrequest->$column, $processedrequest->$column);
        }

        // Make sure that created zip has proper content in it.
        $ziparray = $coursefiles->get_content();
        $this->compare_content($expectedfiles, $ziparray);
    }

    /**
     * Test that if a course suddenly has all its content gone, that we delete
     * the file for any requests that had content.
     */
    public function test_filled_then_emptied_course() {
        // Add content to section 0.
        $contenttocreate[] = array('section' => 0);
        $this->populate_course($contenttocreate);

        // Course should have lots of content now. Create and process request.
        $coursefiles = new block_ucla_course_download_files(
                $this->course->id, $this->teacher->id);
        $result = $coursefiles->add_request();
        $this->assertTrue($result);
        $coursefiles->process_request();
        $this->assertCount(1, $this->sink->get_messages());
        $this->assertEquals('request_completed', $coursefiles->get_request_status());

        // Now delete added file.
        $resources = get_all_instances_in_course('resource', $this->course, $this->teacher->id);
        $todelete = reset($resources);
        course_delete_module($todelete->coursemodule);

        // Redo request.
        $coursefiles = new block_ucla_course_download_files(
                $this->course->id, $this->teacher->id);
        $request = $coursefiles->process_request();
        $this->assertNull($request);
        $this->assertEquals('request_unavailable', $coursefiles->get_request_status());
    }

    /**
     * Make sure that the teacher can see hidden files, but students cannot.
     */
    public function test_hidden_files() {
        // Put visible file in section 1 and hidden file in section 2.
        $contenttocreate[0] = array('section' => 1);
        $contenttocreate[1] = array('section' => 2, 'visible' => 0);
        $expectedteacherfiles = $this->populate_course($contenttocreate);
        $expectedstudentfiles = $expectedteacherfiles;
        unset($expectedstudentfiles[1]);    // Get rid of hidden file.

        // Make sure that each user can only see the files they are allowed.
        foreach (array('teacher', 'student') as $user) {
            $coursedownload = new block_ucla_course_download_files(
                    $this->course->id, $this->$user->id);
            $ziparray = $coursedownload->get_content();
            $expectedfiles = ${'expected'.$user.'files'};
            $this->compare_content($expectedfiles, $ziparray);
        }
    }

    /**
     * Tests that after a request is processed and files are added, then the zip
     * is also updated.
     */
    public function test_refresh_add() {
        // Add content to section 5.
        $contenttocreate[] = array('section' => 5);
        $expectedfiles = $this->populate_course($contenttocreate);

        $coursedownload = new block_ucla_course_download_files(
                $this->course->id, $this->student->id);
        $coursedownload->add_request();
        $request = $coursedownload->process_request();
        $this->assertCount(1, $this->sink->get_messages());
        $ziparray = $coursedownload->get_content();
        $this->compare_content($expectedfiles, $ziparray);

        $orignaltimestamp = $request->timeupdated;
        $orignalfileid = $request->fileid;

        // Redo request. We do not expect changes.
        // Recreate $coursedownload, since class caches files data.
        unset($coursedownload);
        $coursedownload = new block_ucla_course_download_files(
                $this->course->id, $this->student->id);
        $request = $coursedownload->process_request();
        $this->assertEquals($orignaltimestamp, $request->timeupdated);
        $this->assertEquals($orignalfileid, $request->fileid);

        // Now add another file to section 1.
        $morecontent[] = array('section' => 1);
        $morefiles = $this->populate_course($morecontent);
        $expectedfiles = array_merge($expectedfiles, $morefiles);

        // Redo request. Now we expect changes.
        sleep(1);   // Sleep at least 1 second so timestamp changes.
        // Recreate $coursedownload, since class caches files data.
        unset($coursedownload);
        $coursedownload = new block_ucla_course_download_files(
                $this->course->id, $this->student->id);
        $request = $coursedownload->process_request();
        $this->assertGreaterThan($orignaltimestamp, $request->timeupdated);
        $this->assertNotEquals($orignalfileid, $request->fileid);

        // Zip should have updated files.
        $ziparray = $coursedownload->get_content();
        $this->compare_content($expectedfiles, $ziparray);
    }

    /**
     * Tests that after a request is processed and files are deleted or hidden,
     * then the zip is also updated.
     */
    public function test_refresh_delete() {
        // Add content to section 2, 3, 4.
        $contenttocreate[0] = array('section' => 2);
        $contenttocreate[1] = array('section' => 3);
        $contenttocreate[2] = array('section' => 4);
        $expectedfiles = $this->populate_course($contenttocreate);

        $coursedownload = new block_ucla_course_download_files(
                $this->course->id, $this->student->id);
        $coursedownload->add_request();
        $request = $coursedownload->process_request();
        $this->assertCount(1, $this->sink->get_messages());
        $ziparray = $coursedownload->get_content();
        $this->compare_content($expectedfiles, $ziparray);

        $orignaltimestamp = $request->timeupdated;
        $orignalfileid = $request->fileid;

        // Now delete file in section 2.
        $resources = get_all_instances_in_course('resource', $this->course, $this->student->id);
        // Resources are returned by section order, so get first resource.
        $todelete = $resources[0];
        course_delete_module($todelete->coursemodule);
        unset($expectedfiles[0]);

        // And hide file in section 3.
        $tohide = $resources[1];
        set_coursemodule_visible($tohide->coursemodule, 0);
        unset($expectedfiles[1]);
        
        // Redo request.
                
        // We expect that the student will only see the file in section 4.
        sleep(1);   // Sleep at least 1 second so timestamp changes.
        // Recreate $coursedownload, since class caches files data.
        unset($coursedownload);
        $coursedownload = new block_ucla_course_download_files(
                $this->course->id, $this->student->id);
        $request = $coursedownload->process_request();
        $this->assertGreaterThan($orignaltimestamp, $request->timeupdated);
        $this->assertNotEquals($orignalfileid, $request->fileid);

        // Zip should have updated files.
        $ziparray = $coursedownload->get_content();
        $this->compare_content($expectedfiles, $ziparray);
    }


    /**
     * Make sure that if someone requests a course download that we use the
     * previous request.
     */
    public function test_reuse_request() {
        global $DB;

        // Set ziplifetime to a known value (7 days).
        set_config('ziplifetime', 7, 'block_ucla_course_download');

        // Add content to section 1 and 2.
        $contenttocreate[0] = array('section' => 1);
        $contenttocreate[1] = array('section' => 2);
        $this->populate_course($contenttocreate);

        // Redirect all emails to sink.
        $sink = $this->redirectEmails();

        // Make request.
        $coursefiles = new block_ucla_course_download_files(
                $this->course->id, $this->teacher->id);
        $coursefiles->add_request();
        $request1 = $coursefiles->process_request();

         // Now, make request really old (7 days + 1 second).
        $request1->timerequested = $request1->timerequested - 7*DAYSECS - 1;
        $request1->numdownloaded = rand(1, 1000);
        $DB->update_record('ucla_archives', $request1);
        $coursefiles->refresh();
        $request = $coursefiles->process_request();
        $this->assertNull($request);

        // Redo request.
        $coursefiles->add_request();
        $request2 = $coursefiles->process_request();
        $this->assertEquals($request1->id, $request2->id);
        $this->assertEquals($request1->numdownloaded, $request2->numdownloaded);
    }

    /**
     * Tests that a course with the same content for 2 users will share the same
     * zip file.
     */
    public function test_same_content() {
        // Create content for course. Just add 1 file per section.
        $contenttocreate = array();
        $numsections = course_get_format($this->course)->get_last_section_number();
        for ($section=0; $section<=$numsections; $section++) {
            $contenttocreate[] = array('section' => $section);
        }
        $expectedfiles = $this->populate_course($contenttocreate);

        // Create request for teacher.
        $teacherdownload = new block_ucla_course_download_files(
                $this->course->id, $this->teacher->id);
        $teacherdownload->add_request();
        $teacherrequest = $teacherdownload->process_request();
        $this->assertCount(1, $this->sink->get_messages());
        $this->sink->clear();

        // Create request for student.
        $studentdownload = new block_ucla_course_download_files(
                $this->course->id, $this->student->id);
        $studentdownload->add_request();

        // Before request is processed, check sure that if it is processed that
        // it will reuse an existing zip file.
        $request = $studentdownload->get_request();
        $existingrequest = $studentdownload->has_zip($request->contexthash);
        $this->assertNotEmpty($existingrequest);
        $this->assertNotEmpty($existingrequest->fileid);

        // Now, process request.
        $studentrequest = $studentdownload->process_request();
        $this->assertCount(1, $this->sink->get_messages());

        // Make sure that both requests share the same content.
        foreach (array('contexthash', 'content') as $column) {
            $this->assertEquals($teacherrequest->$column, $studentrequest->$column);
        }

        // Make sure that the file both requests point to has the same hash.
        $fs = get_file_storage();
        $teacherfile = $fs->get_file_by_id($teacherrequest->fileid);
        $studentfile = $fs->get_file_by_id($studentrequest->fileid);
        $this->assertEquals($teacherfile->get_contenthash(), $studentfile->get_contenthash());
    }

    /**
     * Make sure that the maxfilesize config setting works as expected.
     */
    public function test_size_limit() {
        // Add content to section 1 and 2.
        $contenttocreate[0] = array('section' => 1);
        $contenttocreate[1] = array('section' => 2);
        $expectedfiles = $this->populate_course($contenttocreate);

        // Make request. Nothing should change.
        $teacherdownload = new block_ucla_course_download_files(
                $this->course->id, $this->teacher->id);
        $teacherdownload->add_request();
        $request1 = $teacherdownload->process_request();
        $this->assertCount(1, $this->sink->get_messages());
        
        $ziparray = $teacherdownload->get_content();
        $this->compare_content($expectedfiles, $ziparray);

        // Now set filesize limit to a know, low amount.
        set_config('maxfilesize', 10, 'block_ucla_course_download'); // 10 MB.

        // Set file size for file in section 1 to something over 10 MB.
        $modinfo = new course_modinfo($this->course, $this->teacher->id);
        $resourcemods = $modinfo->get_instances_of('resource');

        // Assuming first mod is in section 1.
        $resourcemod = reset($resourcemods);
        $modcontext = context_module::instance($resourcemod->id);
        $fs = get_file_storage();
        $fsfiles = $fs->get_area_files($modcontext->id, 'mod_resource',
                'content', 0, 'sortorder DESC, id ASC', false);
        $mainfile = reset($fsfiles);
        $mainfile->set_filesize(11 * pow(1024, 2));  // Set to 11 MB.
        // Calling the deprecated method set_filesize above, but we are ignoring
        // it for now.
        $this->assertDebuggingCalled();

        // Redo request.
        $teacherdownload->refresh();
        $request2 = $teacherdownload->process_request();
        $this->assertNotEquals($request1->fileid, $request2->fileid);

        // Make sure that file is not included.
        $ziparray = $teacherdownload->get_content();
        $smallerexpectedfiles = $expectedfiles;
        unset($smallerexpectedfiles[0]);
        $this->compare_content($smallerexpectedfiles, $ziparray);

        // Now increase limit and make sure file is included.
        set_config('maxfilesize', 15, 'block_ucla_course_download'); // 15 MB.
        $teacherdownload->refresh();
        $request2 = $teacherdownload->process_request();
        $this->assertNotEquals($request1->fileid, $request2->fileid);
        $ziparray = $teacherdownload->get_content();
        $this->compare_content($expectedfiles, $ziparray);
    }

    /**
     * Test that inactive requests are ignored by cron process.
     */
    public function test_ignore_inactive_requests() {
        global $DB;

        // Set ziplifetime to a known value (7 days).
        set_config('ziplifetime', 7, 'block_ucla_course_download');

        // Add content and create initial zip.
        $contenttocreate[] = array('section' => 1);
        $this->populate_course($contenttocreate);
        $coursefiles = new block_ucla_course_download_files(
                $this->course->id, $this->teacher->id);
        $coursefiles->add_request();
        $request = $coursefiles->process_request();
        $this->assertCount(1, $this->sink->get_messages());
        $this->assertNotEmpty($request->fileid);
        $this->assertEquals('request_completed', $coursefiles->get_request_status());

        // Now, make request really old (8 days).
        $request->timerequested = $request->timerequested - (8 * DAYSECS);
        $DB->update_record('ucla_archives', $request);
        $coursefiles->refresh();

        // Process request again
        $request = $coursefiles->process_request();
        $this->assertNull($request);
        $this->assertEquals('request_available', $coursefiles->get_request_status());

        // Make sure that existing request was made inactive.
        $request = $coursefiles->get_request();
        $this->assertEmpty($request);
        $request = $coursefiles->get_request(0);
        $this->assertNotEmpty($request);

        // Check that cron ignores inactive processes.
        ob_start();
        $trace = new text_progress_trace();
        $blockcoursedownload = new block_ucla_course_download();
        $blockcoursedownload->cron($trace);
        $list = ob_get_contents();
        ob_end_clean(); 
        $this->assertEquals("No records to process.\n", $list);
    }
}
