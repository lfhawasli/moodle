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
 * Unit tests for restricting past course access for students.
 *
 * @package    local_ucla
 * @category   test
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/local/ucla/tests/generator/lib.php');

/**
 * PHPunit testcase class.
 *
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group ucla
 * @group local_ucla
 */
class past_course_access_test extends advanced_testcase {

    /**
     * Make sure that hiding a course or TA site also disables guest access.
     */
    public function test_guest_access_disabled() {
        global $DB;

        $enrolguestplugin = enrol_get_plugin('guest');

        /* Some edges that we need to test for:
         *  - Site with more than one guest enrollment plugin
         *  - Site with TA site
         *  - Site with no guest enrollment plugin
         *  - Regular, default site
         */

        // Need to create one more test site.
        $uclagen = $this->getDataGenerator()->get_plugin_generator('local_ucla');
        $uclagen->create_class(array('term' => '131'));

        $summercourses = ucla_get_courses_by_terms(array('131'));
        $this->assertEquals(count($summercourses), 4);

        $i = 0;
        $summercourseids = array();
        foreach ($summercourses as $urcrecord) {
            $record = array_pop($urcrecord);   // Crosslists should not matter.
            $course = $DB->get_record('course', array('id' => $record->courseid));
            $summercourseids[] = $course->id;
            ++$i;
            switch ($i) {
                // Site with more than one guest enrollment plugin.
                case 1:
                    // Sites should already have guest enrollment plugin added.
                    $enrolguestplugin->add_instance($course);
                    $count = $DB->count_records('enrol',
                            array('enrol' => 'guest', 'courseid' => $course->id));
                    $this->assertGreaterThan(1, intval($count));
                    break;
                // Site with TA site.
                case 2:
                    $tasitegenerator = $this->getDataGenerator()->get_plugin_generator('block_ucla_tasites');
                    $tasitegenerator->setup();
                    $tasite = $tasitegenerator->create_instance($course);
                    $summercourseids[] = $tasite->id;
                    break;
                // Site with no guest enrollment plugin.
                case 3:
                    $guestplugins = $DB->get_records('enrol',
                            array('enrol' => 'guest',
                        'courseid' => $course->id));
                    foreach ($guestplugins as $guestplugin) {
                        $enrolguestplugin->delete_instance($guestplugin);
                    }
                    break;
                // Regular, default site.
                default:
                    break;
            }
        }

        // Verify that guest enrollment plugins are active.
        $firstentry = true;
        foreach ($summercourseids as $index => $courseid) {
            $guestplugins = $DB->get_records('enrol',
                    array('enrol' => 'guest',
                'courseid' => $courseid));
            if (!empty($guestplugins)) {
                // Check that at least one plugin is enabled.
                $enabled = ENROL_INSTANCE_DISABLED;
                foreach ($guestplugins as $guestplugin) {
                    if ($guestplugin->status == ENROL_INSTANCE_ENABLED) {
                        $enabled = ENROL_INSTANCE_ENABLED;
                        break;
                    }
                }
                $this->assertEquals(ENROL_INSTANCE_ENABLED, $enabled);
            }
        }

        // Now hide summer courses.
        hide_courses('131');

        // Verify that guest enroll (if exists) is disabled.
        foreach ($summercourseids as $courseid) {
            $guestplugins = $DB->get_records('enrol',
                    array('enrol' => 'guest',
                'courseid' => $courseid));
            if (!empty($guestplugins)) {
                foreach ($guestplugins as $guestplugin) {
                    $this->assertEquals($guestplugin->status,
                            ENROL_INSTANCE_DISABLED);
                }
            }
        }

        // Make sure that other terms were not affected.
        $fallcourses = ucla_get_courses_by_terms(array('13F'));
        foreach ($fallcourses as $courseid => $courseinfo) {
            $guestplugins = $DB->get_records('enrol',
                    array('enrol' => 'guest',
                'courseid' => $courseid));
            foreach ($guestplugins as $guestplugin) {
                $this->assertTrue(!empty($guestplugin));
                // Check that at least one plugin is enabled.
                $enabled = ENROL_INSTANCE_DISABLED;
                foreach ($guestplugins as $guestplugin) {
                    if ($guestplugin->status == ENROL_INSTANCE_ENABLED) {
                        $enabled = ENROL_INSTANCE_ENABLED;
                        break;
                    }
                }
                $this->assertEquals(ENROL_INSTANCE_ENABLED, $enabled);
            }
        }
    }

    /**
     * Make sure that no courses are hidden if
     * 'local_ucla'|'student_access_ends_week' is not set.
     */
    public function test_not_set() {
        global $DB;
        // Make sure config setting is not set.
        set_config('student_access_ends_week', null, 'local_ucla');

        // Make sure no courses are hidden.
        $anyhidden = $DB->record_exists('course', array('visible' => '0'));
        $this->assertFalse($anyhidden);

        // Call method to auto hide courses for every week possible.
        $weeks = range(0, 11);
        foreach ($weeks as $week) {
            $event = block_ucla_weeksdisplay\event\week_changed::create(
                    array('other' => array('week' => $week)));
            $event->trigger();
            $this->assertDebuggingNotCalled();
        }

        // Now make sure there are still no hidden courses.
        $anyhidden = $DB->record_exists('course', array('visible' => '0'));
        $this->assertFalse($anyhidden);
    }

    /**
     * Make sure that summer gets weeks_changed trigger set.
     *
     * See CCLE-9000 - Week changed event not being triggered during summer sesssions.
     */
    public function test_summer_hiding() {
        global $DB;

        // Make sure config setting is set.
        set_config('student_access_ends_week', 3, 'local_ucla');

        $terms = array(
            '131' => array(
                array(
                    'term' => '131',
                    'session' => '1A',
                    'session_start' => '2013-06-24',
                    'session_end' => '2013-08-30',
                    'instruction_start' => '2013-06-24',
                    'term_start' => '2013-06-24',
                    'term_end' => '2013-09-13',
                ),
                array(
                    'term' => '131',
                    'session' => '6A',
                    'session_start' => '2013-06-24',
                    'session_end' => '2013-08-02',
                    'instruction_start' => '2013-06-24',
                    'term_start' => '2013-06-24',
                    'term_end' => '2013-09-13',
                ),
                array(
                    'term' => '131',
                    'session' => '6C',
                    'session_start' => '2013-08-05',
                    'session_end' => '2013-09-13',
                    'instruction_start' => '2013-08-05',
                    'term_start' => '2013-06-24',
                    'term_end' => '2013-09-13',
                ),
                array(
                    'term' => '131',
                    'session' => '8A',
                    'session_start' => '2013-06-24',
                    'session_end' => '2013-08-16',
                    'instruction_start' => '2013-06-24',
                    'term_start' => '2013-06-24',
                    'term_end' => '2013-09-13',
                ),
            ),
        );

        // Set to Summer week 3.
        $summer = \block_ucla_weeksdisplay_session::create($terms['131']);
        $today = new DateTime('2013-07-08');
        $summer->set_today($today);

        // Week changes event would trigger, calling hide_past_courses().
        $summer->update_week_display();
        $email = $this->getDebuggingMessages();
        $this->assertContains('Hid 3 courses', $email[0]->message);
        $this->assertDebuggingCalled();

        $courses = ucla_get_courses_by_terms('131');
        foreach ($courses as $courseid => $course) {
            $ishidden = $DB->record_exists('course',
                    array('id' => $courseid, 'visible' => 0));
            $this->assertTrue($ishidden);
        }
    }

    /**
     * Make sure that TA sites are hidden as well as course sites.
     */
    public function test_ta_site_hiding() {
        global $DB;
        // Make sure config setting is set.
        set_config('student_access_ends_week', 3, 'local_ucla');

        // Create TA sites for courses in Summer 2013.
        $summercourses = ucla_get_courses_by_terms(array('131'));
        $this->assertFalse(empty($summercourses));

        $tasitegenerator = $this->getDataGenerator()->get_plugin_generator('block_ucla_tasites');
        $tasitegenerator->setup();
        foreach ($summercourses as $courseid => $courseinfo) {
            $course = get_course($courseid);
            $tasitegenerator->create_instance($course);
        }

        // Make sure no courses are hidden.
        $anyhidden = $DB->record_exists('course', array('visible' => '0'));
        $this->assertFalse($anyhidden);

        // Now try week 3 and make sure that Summer 2013 TA sites are hidden.
        $event = block_ucla_weeksdisplay\event\week_changed::create(
                array ('other' => array('week' => 3)));
        $event->trigger();
        $email = $this->getDebuggingMessages();
        $this->assertContains('Hid 3 TA sites', $email[0]->message);
        $this->assertDebuggingCalled();
        $summercourses = ucla_get_courses_by_terms('131');
        foreach ($summercourses as $courseid => $course) {
            $existingtasites = block_ucla_tasites::get_tasites($courseid);
            foreach ($existingtasites as $tasite) {
                $ishidden = $DB->record_exists('course',
                        array('id' => $tasite->id, 'visible' => 0));
                $this->assertTrue($ishidden);
            }
        }

        $otherterms = array('13S', '13F', '14W');
        foreach ($otherterms as $term) {
            $courses = ucla_get_courses_by_terms($term);
            foreach ($courses as $courseid => $course) {
                $ishidden = $DB->record_exists('course',
                        array('id' => $courseid, 'visible' => 0));
                $this->assertFalse($ishidden);
            }
        }
    }

    /**
     * Make sure that if we set the 'student_access_ends_week' to 3, that only
     * when it is the 3rd week that previous term courses are hidden.
     */
    public function test_third_week_config() {
        global $DB;
        // Make sure config setting is set.
        set_config('student_access_ends_week', 3, 'local_ucla');

        // Make sure no courses are hidden.
        $anyhidden = $DB->record_exists('course', array('visible' => '0'));
        $this->assertFalse($anyhidden);

        // Make sure that week 0, 1, 2.
        $weeks = array(0, 1, 2);
        foreach ($weeks as $week) {
            $event = block_ucla_weeksdisplay\event\week_changed::create(
                    array ('other' => array('week' => $week)));
            $event->trigger();
            $this->assertDebuggingNotCalled();
        }

        // Make sure no courses are hidden.
        $anyhidden = $DB->record_exists('course', array('visible' => '0'));
        $this->assertFalse($anyhidden);

        // Now try week 3 and make sure that only Summer 2013 courses are hidden.
        $event = block_ucla_weeksdisplay\event\week_changed::create(
                array('other' => array('week' => 3)));
        $event->trigger();
        $email = $this->getDebuggingMessages();
        $this->assertContains('Hiding courses for 131', $email[0]->message);
        $this->assertContains('Hid 3 courses', $email[0]->message);
        $this->assertDebuggingCalled();
        $summercourses = ucla_get_courses_by_terms('131');
        foreach ($summercourses as $courseid => $course) {
            $ishidden = $DB->record_exists('course',
                    array('id' => $courseid, 'visible' => 0));
            $this->assertTrue($ishidden);
        }

        $otherterms = array('13S', '13F', '14W');
        foreach ($otherterms as $term) {
            $courses = ucla_get_courses_by_terms($term);
            foreach ($courses as $courseid => $course) {
                $ishidden = $DB->record_exists('course',
                        array('id' => $courseid, 'visible' => 0));
                $this->assertFalse($ishidden);
            }
        }

        // Now unhide one summer course and try week 4, make sure that unhidden
        // course is not rehidden.
        $unhidecourse = array_pop($summercourses);
        list($unhidecourse, $courseid) = array(end($summercourses), key($summercourses));
        $DB->set_field('course', 'visible', 1, array('id' => $courseid));

        $event = block_ucla_weeksdisplay\event\week_changed::create(
                array('other' => array('week' => 4)));
        $event->trigger();
        $this->assertDebuggingNotCalled();
        $ishidden = $DB->record_exists('course',
                array('id' => $courseid, 'visible' => 0));
        $this->assertFalse($ishidden);
    }

    /**
     * Create some default courses.
     */
    protected function setUp() {
        $this->resetAfterTest(true);

        $uclagen = $this->getDataGenerator()->get_plugin_generator('local_ucla');

        // Set current term.
        set_config('currentterm', '13F');

        // Create some courses for several terms 13S/131/13F/14W.
        $terms = array('13S', '131', '13F', '14W');
        foreach ($terms as $term) {
            $uclagen->create_class(array('term' => $term));
            $uclagen->create_class(array('term' => $term));
            $uclagen->create_class(array('term' => $term));
        }

        // Function local_ucla_observer::hide_past_courses will attempt to send
        // email, but will output debugging messages instead. We will use
        // assertDebuggingCalled() and getDebuggingMessages() to verify
        // email message.
        unset_config('noemailever');
        set_config('admin_email', 'ccle@ucla.edu', 'local_ucla');
    }

}
