<?php
// This file is part of the UCLA course creator plugin for Moodle - http://moodle.org/
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
 * Tests the event handlers for the UCLA course creator plugin.
 *
 * @package    tool_uclacoursecreator
 * @category   test
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/uclacoursecreator/eventlib.php');

/**
 * PHPunit testcase class.
 *
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group ucla
 * @group tool_uclacoursecreator
 */
class eventlib_test extends advanced_testcase {

    /**
     * Sets up the configuration variables needed to test the MyUCLA url updater.
     */
    protected function setUp() {
        $this->resetAfterTest();

        // Since PHPunit has no access to the regular config.php $CFG variables
        // we need to look for some global variables.
        if (!defined('MYUCLA_URL_UPDATER_TEST_CONFIG_URL') ||
                !defined('MYUCLA_URL_UPDATER_TEST_CONFIG_NAME') ||
                !defined('MYUCLA_URL_UPDATER_TEST_CONFIG_EMAIL')) {
            $this->markTestSkipped('To run MyUCLA url updater unit tests you must setup some global variables.');
        }

        set_config('url_service', MYUCLA_URL_UPDATER_TEST_CONFIG_URL,
                'tool_myucla_url');
        set_config('user_name', MYUCLA_URL_UPDATER_TEST_CONFIG_NAME,
                'tool_myucla_url');
        set_config('user_email', MYUCLA_URL_UPDATER_TEST_CONFIG_EMAIL,
                'tool_myucla_url');
        if (defined('MYUCLA_URL_UPDATER_TEST_CONFIG_OVERRIDE_DEBUGGING')) {
            set_config('override_debugging',
                    MYUCLA_URL_UPDATER_TEST_CONFIG_OVERRIDE_DEBUGGING,
                    'tool_myucla_url');
        }
    }

    /**
     * See if ucla_request_classes and ucla_reg_classinfo entries are deleted
     * when a crosslisted course is deleted.
     */
    public function test_delete_crosslist_course() {
        global $DB;

        // Create crosslisted course and delete it.
        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array(), array());
        $course = reset($class);
        delete_course($course->courseid);

        // Make sure ucla_request_classes entries are empty.
        $exists = $DB->record_exists('ucla_request_classes',
                array('courseid' => $course->courseid));
        $this->assertFalse($exists);

        // Make sure ucla_reg_classinfo entries are empty.
        foreach ($class as $entry) {
            $exists = $DB->record_exists('ucla_reg_classinfo',
                    array('term' => $entry->term, 'srs' => $entry->srs));
            $this->assertFalse($exists);
        }
    }

    /**
     * See if ucla_request_classes and ucla_reg_classinfo entries are deleted
     * when a non-crosslisted course is deleted.
     */
    public function test_delete_noncrosslist_course() {
        global $DB;

        // Create non-crosslisted course and delete it.
        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array());
        $course = reset($class);
        delete_course($course->courseid);

        // Make sure ucla_request_classes entries are empty.
        $exists = $DB->record_exists('ucla_request_classes',
                array('courseid' => $course->courseid));
        $this->assertFalse($exists);

        // Make sure ucla_reg_classinfo entries are empty.
        foreach ($class as $entry) {
            $exists = $DB->record_exists('ucla_reg_classinfo',
                    array('term' => $entry->term, 'srs' => $entry->srs));
            $this->assertFalse($exists);
        }
    }

    /**
     * Test clearing of an existing MyUCLA url.
     */
    public function test_existing_myuclaurl() {
        global $CFG;

        $cc = new uclacoursecreator();
        $myuclarlupdater = $cc->get_myucla_urlupdater();

        // URL exist and is for current server, then should clear it.
        // First create url for course.
        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array());
        $course = reset($class);

        $courseurl = array('term' => $course->term,
            'srs' => $course->srs,
            'url' => $CFG->wwwroot . '/course/view.php?id=' . $course->courseid);
        $result = $myuclarlupdater->send_MyUCLA_urls(array($courseurl), true);
        $pos = strpos(array_pop($result),
                $myuclarlupdater::expected_success_message);
        $this->assertTrue($pos !== false);

        // Now delete course.
        delete_course($course->courseid);

        // Verify that myucla url is deleted.
        $result = $myuclarlupdater->send_MyUCLA_urls(array($courseurl));
        $result = array_pop($result);
        $this->assertTrue(empty($result));
    }

    /**
     * Test clearing of an existing MyUCLA url for a crosslisted course.
     */
    public function test_existing_myuclaurl_crosslisted() {
        global $CFG;

        $cc = new uclacoursecreator();
        $myuclarlupdater = $cc->get_myucla_urlupdater();

        // URL exist and is for current server, then should clear it.
        // First create urls for course.
        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array(), array());
        $course = reset($class);

        foreach ($class as $crosslist) {
            $courseurl = array('term' => $crosslist->term,
                'srs' => $crosslist->srs,
                'url' => $CFG->wwwroot . '/course/view.php?id=' . $crosslist->courseid);
            $result = $myuclarlupdater->send_MyUCLA_urls(array($courseurl), true);
            $pos = strpos(array_pop($result),
                    $myuclarlupdater::expected_success_message);
            $this->assertTrue($pos !== false);
        }

        // Now delete course.
        delete_course($course->courseid);

        // Verify that myucla urls are deleted.
        foreach ($class as $crosslist) {
            $courseurl = array('term' => $crosslist->term,
                'srs' => $crosslist->srs);
            $result = $myuclarlupdater->send_MyUCLA_urls(array($courseurl));
            $result = array_pop($result);
            $this->assertTrue(empty($result));
        }
    }

    /**
     * Test not clearing of an existing MyUCLA url that isn't on current server.
     */
    public function test_existing_nonlocal_myuclaurl() {
        global $CFG;

        $cc = new uclacoursecreator();
        $myuclarlupdater = $cc->get_myucla_urlupdater();

        // URL exist and is not for current server, then should not clear it.
        // First create url for course that points to non-local server.
        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array());
        $course = reset($class);

        $courseurl = array('term' => $course->term,
            'srs' => $course->srs,
            'url' => 'http://ucla.edu');
        $result = $myuclarlupdater->send_MyUCLA_urls(array($courseurl), true);
        $pos = strpos(array_pop($result),
                $myuclarlupdater::expected_success_message);
        $this->assertTrue($pos !== false);

        // Now delete course.
        delete_course($course->courseid);

        // Verify that myucla url is not deleted.
        $result = $myuclarlupdater->send_MyUCLA_urls(array($courseurl));
        $result = array_pop($result);
        $this->assertEquals($result, $courseurl['url']);
    }

}
