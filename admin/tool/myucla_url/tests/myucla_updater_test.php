<?php
// This file is part of the MyUCLA url updater for Moodle - http://moodle.org/
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
 * Unit tests for myucla_urlupdater.class.php.
 *
 * @package    tool_myucla_url
 * @category   test
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/myucla_url/myucla_urlupdater.class.php');

/**
 * PHPunit testcase class.
 * 
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group ucla
 * @group tool_myucla_url
 */
class myucla_urlupdater_test extends advanced_testcase {

    /**
     * Instance of myucla_urlupdater class.
     * @var myucla_urlupdater
     */
    private $urlupdater = null;

    /**
     * Try updating a url when you do not have access to the MyUCLA url service.
     * Should get an error returned.
     */
    public function test_access_denied_update() {
        // Overwrite previous value of the 'url_service' config variable.
        if (!defined('MYUCLA_URL_UPDATER_TEST_CONFIG_ACCESSDENIED_URL')) {
            $this->markTestSkipped('To run this MyUCLA url updater unit test you must setup the access denied url.');
        }
        set_config('url_service',
                MYUCLA_URL_UPDATER_TEST_CONFIG_ACCESSDENIED_URL,
                'tool_myucla_url');

        $course = array('term' => '12W',
            'srs' => '123456789',
            'url' => 'http://ucla.edu');

        // Try to set URL at MyUCLA. Expecting result to only contain failures.
        $this->assertEmpty($this->urlupdater->failed);
        $this->urlupdater->sync_MyUCLA_urls(array('12W-123456789' => $course));
        $this->assertEmpty($this->urlupdater->successful);
        $this->assertNotEmpty($this->urlupdater->skipped);
        $this->assertNotEmpty($this->urlupdater->failed);
    }

    /**
     * Try to set a valid course's url.
     */
    public function test_setting_valid_course() {
        $course = array('term' => '12W',
            'srs' => '123456789',
            'url' => 'http://ucla.edu');
        $result = $this->set_url($course);
        $this->assertTrue($result);
    }

    /**
     * Try to set an invalid course's url.
     */
    public function test_setting_invalid_course() {
        $course = array('term' => '12W',
            'srs' => '12345678',
            'url' => 'http://ucla.edu');
        $result = $this->set_url($course);
        $this->assertFalse($result);
    }

    /**
     * Try to set a complex URL to test encoding/decoding. 
     */
    public function test_setting_complex_url() {
        $testurl = 'http://ucla.edu/index.php?id=something&id2=somewhere';
        $course = array('term' => '12W',
            'srs' => '123456789',
            'url' => $testurl);
        $result = $this->set_url($course);
        $this->assertTrue($result);

        // Now get url and make sure it matches.
        $result = $this->get_url($course);
        $this->assertTrue($testurl == $result);
    }

    /**
     * Try to set, get, and clear url using empty string.
     */
    public function test_set_get_clear_with_empty() {
        $testurl = 'http://ucla.edu';
        $course = array('term' => '12W',
            'srs' => '123456789',
            'url' => $testurl);
        $result = $this->set_url($course);
        $this->assertTrue($result);

        // Now get url and make sure it matches.
        $result = $this->get_url($course);
        $this->assertTrue($testurl == $result);

        // Now clear it.
        $course['url'] = '';
        $result = $this->set_url($course);
        $this->assertTrue($result);

        // Get it to make sure it is clear.
        $result = $this->get_url($course);
        $this->assertTrue(empty($result));
    }

    /**
     * Try to set, get, and clear url using null.
     */
    public function test_set_get_clear_with_null() {
        $testurl = 'http://ucla.edu';
        $course = array('term' => '12W',
            'srs' => '123456789',
            'url' => $testurl);
        $result = $this->set_url($course);
        $this->assertTrue($result);

        // Now get url and make sure it matches.
        $result = $this->get_url($course);
        $this->assertTrue($testurl == $result);

        // Now clear it.
        $course['url'] = null;
        $result = $this->set_url($course);
        $this->assertTrue($result);

        // Get it to make sure it is clear.
        $result = $this->get_url($course);
        $this->assertTrue(empty($result));
    }

    /**
     * Try syncing a variety of course urls (both set and unset) and an invalid 
     * course.
     */
    public function test_sync() {
        $setcourse = array('term' => '12W',
            'srs' => '123456789',
            'url' => 'http://ucla.edu');
        $unsetcourse = array('term' => '12W',
            'srs' => '987654321',
            'url' => 'http://newsroom.ucla.edu');
        $invalidcourse = array('term' => '12W',
            'srs' => '12345678',
            'url' => 'http://www.usc.edu');

        // First set $setcourse.
        $result = $this->set_url($setcourse);
        $this->assertTrue($result);

        // Make sure that $unsetcourse is unset.
        $unsetcoursetmp = $unsetcourse;
        $unsetcoursetmp['url'] = null;
        $result = $this->set_url($unsetcoursetmp);
        $this->assertTrue($result);

        // Then try to sync them all.
        $courses['set'] = $setcourse;
        $courses['unset'] = $unsetcourse;
        $courses['invalid'] = $invalidcourse;
        $this->urlupdater->sync_MyUCLA_urls($courses);

        // Make sure that $unsetcourse has a success message.
        $success = $this->urlupdater->successful;
        // Make sure that $setcourse's url was returned (meaning if existed already).
        $this->assertTrue($success['set'] == $setcourse['url']);
        $this->assertTrue(false !== strpos($success['unset'],
                        myucla_urlupdater::expected_success_message));

        // Make sure that $invalidcourse has an error message.
        $failed = $this->urlupdater->failed;
        $this->assertTrue(false === strpos($failed['invalid'],
                        myucla_urlupdater::expected_success_message));
    }

    /**
     * Helper method. Gets url from MyUCLA for given course.
     * 
     * @param mixed $course Expects course to be a multi-dimensonal array with
     *                      following keys: term, srs, url
     * @return string       Returns url from MyUCLA service call 
     */
    protected function get_url($course) {
        $result = $this->urlupdater->send_MyUCLA_urls(array($course));
        return array_pop($result);  // Returns indexed array.
    }

    /**
     * Helper method. Sends given course and url to MyUCLA.
     * 
     * @param mixed $course Expects course to be a multi-dimensonal array with
     *                      following keys: term, srs, url
     * @return boolean      Returns true if URL was set, otherwise false. 
     */
    protected function set_url($course) {
        $result = $this->urlupdater->send_MyUCLA_urls(array($course),
                true);
        return false !== strpos(array_pop($result),
                        myucla_urlupdater::expected_success_message);
    }

    /**
     * Creates instance of MyUCLA url updater class.
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

        $this->urlupdater = new myucla_urlupdater();
    }

    /**
     * Destroys instance of MyUCLA url updater class.
     */
    protected function tearDown() {
        $this->urlupdater = null;
    }

}
