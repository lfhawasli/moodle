<?php
// This file is part of Moodle - http://moodle.org/
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
 * Class and function library for syllabus plugin.
 *
 * @package     local_ucla_syllabus
 * @category    test
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../webservice/lib.php');

/**
 * Subclass of syllabus_ws_item to force failures and success.
 *
 * This is very synthetic, does not handle actual web transport failures.
 *
 * @package     local_ucla_syllabus
 * @category    test
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ws_test_item extends syllabus_ws_item {
    /** @var bool $success */
    protected $success;
    /** @var int $timestamp */
    public $timestamp;
    /** @var boolean $hasemailed */
    public $hasemailed;

    /**
     * Constructor.
     *
     * @param boolean $success set to default true.
     */
    public function __construct($success = true) {
        $this->success = $success;
        $this->hasemailed = false;

        parent::__construct(array(), array());
    }

    /**
     * Post.
     *
     * @param object $payload
     * @return boolean $success
     */
    protected function _post($payload) {
        return $this->success;
    }

    /**
     * Match Criteria.
     */
    protected function _match_criteria() {
        return true;
    }

    /**
     * Contact.
     *
     * @param object $payload
     * @return boolean
     */
    protected function _contact($payload) {
        $this->timestamp = time();
        $this->hasemailed = true;

        return true;
    }
}

/**
 * Class Tests the webservice email notification.
 *
 * It makes sure that user does not get flooded with emails every cron run.
 *
 * @package     local_ucla_syllabus
 * @category    test
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class syllabus_webservice_test extends advanced_testcase {
    /**
     * Reset database after each testcase.
     */
    protected function setUp() {

        $this->resetAfterTest(true);
    }

    public function test_post_success() {

        $wsitem = new ws_test_item();

        $payload = new stdClass;
        $payload->srs = '111000';

        // Check that no alert waiting.
        $alert = get_config('next_attempt_alert_'.$payload->srs, 'local_ucla_syllabus');
        $this->assertFalse($alert);

        // Will succeed.
        $wsitem->notify($payload);

        // Check that no alert was set.
        $alert = get_config('next_attempt_alert_'.$payload->srs, 'local_ucla_syllabus');
        $this->assertFalse($alert);

    }

    public function test_post_fail() {

        $wsitem = new ws_test_item(false);

        $payload = new stdClass;
        $payload->srs = '111000';

        // Check that no alert waiting.
        $alert = get_config('next_attempt_alert_'.$payload->srs, 'local_ucla_syllabus');
        $this->assertFalse($alert);

        // Will fail.
        $wsitem->notify($payload);

        // Check that user was emailed.
        $this->assertTrue($wsitem->hasemailed);

        // Check that alert was set.
        $alert = get_config('next_attempt_alert_'.$payload->srs, 'local_ucla_syllabus');
        $this->assertTrue((bool)$alert);
    }

    public function test_resend() {

        $wsitem = new ws_test_item(false);

        $payload = new stdClass;
        $payload->srs = '111000';

        // Check that no alert waiting.
        $alert = get_config('next_attempt_alert_'.$payload->srs, 'local_ucla_syllabus');
        $this->assertFalse($alert);

        // Will fail, so user will be emailed.
        $wsitem->notify($payload);
        $this->assertTrue($wsitem->hasemailed);

        // Let's create a new syllabus WS object that will fail, and check
        // if user gets emailed.
        $anotherwsitem = new ws_test_item(false);
        $anotherwsitem->notify($payload);
        $this->assertFalse($anotherwsitem->hasemailed);

        // Check that alert remails.
        $alerttime = get_config('next_attempt_alert_'.$payload->srs, 'local_ucla_syllabus');
        $this->assertTrue((bool)$alerttime);

        // Set the alert 2 hours back.
        set_config('local_ucla_syllabus', time() - $anotherwsitem::NEXT_ATTEMPT, 'next_attempt_alert_'.$payload->srs);

        // Check that user was emailed.
        $anotherwsitem->notify($payload);
        $this->assertTrue($anotherwsitem->hasemailed);

        // Alert should still exist.
        $alert = get_config('next_attempt_alert_'.$payload->srs, 'local_ucla_syllabus');
        $this->assertTrue((bool)$alert);

        // Attempt another time, but this time succeed.
        $successpost = new ws_test_item();
        $successpost->notify($payload);

        // Alert should be gone.
        $alert = get_config('next_attempt_alert_'.$payload->srs, 'local_ucla_syllabus');
        $this->assertFalse($alert);
    }
}