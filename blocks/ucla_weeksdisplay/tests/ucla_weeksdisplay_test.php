<?php
// This file is part of the UCLA weeks display block for Moodle - http://moodle.org/
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
 * Unit tests for the UCLA weeks display block.
 *
 * @package    block_ucla_weeksdisplay
 * @category   test
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/ucla_weeksdisplay/block_ucla_weeksdisplay.php'); // Include the code to test.

/**
 * Extends ucla_session to add some new powers. 
 *
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ucla_session_ext extends ucla_session {

    /**
     * Constructor.
     * 
     * @param array $session    Term information.
     * @param string $today     Date format: YYYY-MM-DD.
     */
    public function __construct($session, $today) {
        parent::__construct($session);
        $this->_today = $today;
    }

    /**
     * Setter for _today.
     *
     * @param string $today     Date format: YYYY-MM-DD.
     */
    public function update_today($today) {
        $this->_today = $today;
    }

}

/**
 * PHPunit testcase class.
 *
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group ucla
 * @group block_ucla_weeksdisplay
 */
class ucla_weeksdisplay_test extends advanced_testcase {

    /**
     * Fake registrar calls with real registrar data.  This is to avoid calling
     * registrar directly through tests.  The data was built from
     * stored procedure: ucla_getterms.
     *
     * To add more tests, update this data.
     *
     * @param string $term
     * @return array
     */
    private function registrar_query($term) {
        // This list was retrieved from the registrar.
        $terms = array(
            '121' => array(
                array(
                    'term' => '121',
                    'session' => '8A',
                    'session_start' => '2012-06-25',
                    'session_end' => '2012-08-17',
                    'instruction_start' => '2012-06-25',
                ),
                array(
                    'term' => '121',
                    'session' => '6C',
                    'session_start' => '2012-08-06',
                    'session_end' => '2012-09-14',
                    'instruction_start' => '2012-08-06',
                ),
            ),
            '12F' => array(
                array(
                    'term' => '12F',
                    'session' => 'RG',
                    'session_start' => '2012-09-24',
                    'session_end' => '2012-12-14',
                    'instruction_start' => '2012-09-27',
                ),
            ),
            '13W' => array(
                array(
                    'term' => '13W',
                    'session' => 'RG',
                    'session_start' => '2013-01-02',
                    'session_end' => '2013-03-22',
                    'instruction_start' => '2013-01-07',
                ),
            ),
            '13S' => array(
                array(
                    'term' => '13S',
                    'session' => 'RG',
                    'session_start' => '2013-03-27',
                    'session_end' => '2013-06-14',
                    'instruction_start' => '2013-04-01',
                ),
            ),
        );

        return $terms[$term];
    }

    /**
     * Reset database on every run.
     */
    protected function setUp() {
        $this->resetAfterTest(true);
    }

    /**
     * Test Fall 2012 session and rolloever into winter break.
     */
    public function test_fall_2012() {

        // Fall 2012 session start.
        $today = '2012-09-24';

        // Test ability to set term.
        block_ucla_weeksdisplay::init_currentterm($today);
        $this->assertEquals('12F', get_config('', 'currentterm'));

        // At start of session.
        $query = $this->registrar_query('12F');
        $session = new ucla_session_ext($query, $today);
        $session->update();

        // Session has started, but instructio hasn't.
        $this->assertEquals('<div class="weeks-display label-fall">Fall 2012</div>',
                get_config('local_ucla', 'current_week_display'));
        $this->assertEquals('-1', get_config('local_ucla', 'current_week'));
        $this->assertEquals('12F,13W,13S,131',
                get_config('local_ucla', 'active_terms'));

        // At the start of instruction, display week 0.
        $today = date('Y-m-d', strtotime('+3 days', strtotime($today)));
        $session->update_today($today);
        $session->update();

        $this->assertEquals('<div class="weeks-display label-fall">' .
                '<span class="session ">Fall 2012 - </span>' .
                '<span class="week">Week 0</span></div>',
                get_config('local_ucla', 'current_week_display'));
        $this->assertEquals('0', get_config('local_ucla', 'current_week'));
        $this->assertEquals('12F,13W,13S,131',
                get_config('local_ucla', 'active_terms'));

        // Check Week 1.
        $today = date('Y-m-d', strtotime('+4 days', strtotime($today)));
        $session->update_today($today);
        $session->update();

        $this->assertEquals('<div class="weeks-display label-fall">' .
                '<span class="session ">Fall 2012 - </span>' .
                '<span class="week">Week 1</span></div>',
                get_config('local_ucla', 'current_week_display'));
        $this->assertEquals('1', get_config('local_ucla', 'current_week'));
        $this->assertEquals('12F,13W,13S,131',
                get_config('local_ucla', 'active_terms'));

        // Check Week 10.
        $today = date('Y-m-d', strtotime('+9 weeks', strtotime($today)));
        $session->update_today($today);
        $session->update();

        $this->assertEquals('<div class="weeks-display label-fall">' .
                '<span class="session ">Fall 2012 - </span>' .
                '<span class="week">Week 10</span></div>',
                get_config('local_ucla', 'current_week_display'));
        $this->assertEquals('10', get_config('local_ucla', 'current_week'));
        $this->assertEquals('12F,13W,13S,131',
                get_config('local_ucla', 'active_terms'));

        // Check Finals Week.
        $today = date('Y-m-d', strtotime('+1 week', strtotime($today)));
        $session->update_today($today);
        $session->update();

        // First make sure we didn't screw up $today.
        $this->assertEquals('Monday December 10',
                date('l F j', strtotime($today)));
        $this->assertEquals('<div class="weeks-display label-fall">' .
                '<span class="session ">Fall 2012 - </span>' .
                '<span class="week">Finals week</span></div>',
                get_config('local_ucla', 'current_week_display'));
        $this->assertEquals('11', get_config('local_ucla', 'current_week'));
        $this->assertEquals('12F,13W,13S,131',
                get_config('local_ucla', 'active_terms'));

        // Check the switch to Winter term.. this happens at session END.
        $today = date('Y-m-d', strtotime('+5 days', strtotime($today)));
        $session->update_today($today);
        $session->update();

        // Still final's week, haven't changed terms.
        $this->assertEquals('<div class="weeks-display label-fall">' .
                '<span class="session ">Fall 2012 - </span>' .
                '<span class="week">Finals week</span></div>',
                get_config('local_ucla', 'current_week_display'));
        $this->assertEquals('-1', get_config('local_ucla', 'current_week'));
        $this->assertEquals('12F,13W,13S,131',
                get_config('local_ucla', 'active_terms'));
        $this->assertEquals('13W', get_config('', 'currentterm'));

        unset($session);

        // Turnover to Winter with brand new registrar query, we should be
        // displaying Winter break.
        $today = date('Y-m-d', strtotime('+1 week', strtotime($today)));
        $query = $this->registrar_query('13W');
        $session = new ucla_session_ext($query, $today);
        $session->update();

        $this->assertEquals('<div class="weeks-display label-winter">Winter break</div>',
                get_config('local_ucla', 'current_week_display'));
        $this->assertEquals('-1', get_config('local_ucla', 'current_week'));
        $this->assertEquals('13W,13S,131,13F',
                get_config('local_ucla', 'active_terms'));
        $this->assertEquals('13W', get_config('', 'currentterm'));
    }

    /**
     * Tests that the next_term() method works properly.
     */
    public function test_next_term() {
        // Setting any session, because it doesn't matter.
        $query = $this->registrar_query('12F');
        $uclasession = new ucla_session($query);

        // Array with input and expected output.
        $testcases = array('121' => '12F', '99F' => '00W', '11W' => '11S',
            '13S' => '131', '92F' => '93W');
        foreach ($testcases as $term => $expected) {
            $actual = $uclasession->next_term($term);
            $this->assertEquals($expected, $actual);
        }
    }

    /**
     * Tests the weeks display output for summer 2012 session and rollover
     * into Fall 2012.
     */
    public function test_summer_2012() {

        // Test summer 12 sessions A & C.
        $today = '2012-06-25';

        // Test ability to set term.
        block_ucla_weeksdisplay::init_currentterm($today);
        $this->assertEquals('121', get_config('', 'currentterm'));

        // At start of session A.
        $query = $this->registrar_query('121');
        $session = new ucla_session_ext($query, $today);
        $session->update();

        // For this particular summer, instruction starts at same time as
        // session begins.
        $this->assertEquals('<span class="session ">Summer 2012 - Session A, ' .
                '</span><span class="week">Week 1</span>',
                get_config('local_ucla', 'current_week_display'));
        $this->assertEquals('1', get_config('local_ucla', 'current_week'));
        $this->assertEquals('121,12F,13W,13S',
                get_config('local_ucla', 'active_terms'));

        // Start session C.
        $today = date('Y-m-d', strtotime('+6 weeks', strtotime($today)));
        $session->update_today($today);
        $session->update();

        $this->assertEquals('<span class="session ">Summer 2012 - Session A, ' .
                '</span><span class="week">Week 7</span> | ' .
                '<span class="session ">Summer 2012 - Session C, </span>' .
                '<span class="week">Week 1</span>',
                get_config('local_ucla', 'current_week_display'));
        $this->assertEquals('7', get_config('local_ucla', 'current_week'));
        $this->assertEquals('121,12F,13W,13S',
                get_config('local_ucla', 'active_terms'));

        // Next week.
        $today = date('Y-m-d', strtotime('+1 weeks', strtotime($today)));
        $session->update_today($today);
        $session->update();

        $this->assertEquals('<span class="session ">Summer 2012 - Session A, ' .
                '</span><span class="week">Week 8</span> | ' .
                '<span class="session ">Summer 2012 - Session C, </span>' .
                '<span class="week">Week 2</span>',
                get_config('local_ucla', 'current_week_display'));
        $this->assertEquals('8', get_config('local_ucla', 'current_week'));
        $this->assertEquals('121,12F,13W,13S',
                get_config('local_ucla', 'active_terms'));

        // Session A end.
        $today = date('Y-m-d', strtotime('+1 weeks', strtotime($today)));
        $session->update_today($today);
        $session->update();

        $this->assertEquals('<span class="session ">Summer 2012 - Session C, ' .
                '</span><span class="week">Week 3</span>',
                get_config('local_ucla', 'current_week_display'));
        $this->assertEquals('8', get_config('local_ucla', 'current_week'));
        $this->assertEquals('121,12F,13W,13S',
                get_config('local_ucla', 'active_terms'));
        $this->assertEquals('121', get_config('', 'currentterm'));

        // Session C end.
        // This is between sessions, but the term should have been updated to
        // Fall. The updated term will be used to make the next call to the
        // registrar, which will trigger the changes.
        $today = date('Y-m-d', strtotime('+4 weeks', strtotime($today)));
        $session->update_today($today);
        $session->update();

        $this->assertEquals('Summer 2012',
                get_config('local_ucla', 'current_week_display'));
        $this->assertEquals('-1', get_config('local_ucla', 'current_week'));
        $this->assertEquals('121,12F,13W,13S',
                get_config('local_ucla', 'active_terms'));
        $this->assertEquals('12F', get_config('', 'currentterm'));

        // Check that we transition over to Fall correctly an hour later.
        // To do this, we need to make a fresh registrar call.
        unset($session);

        $today = date('Y-m-d', strtotime('+1 hour', strtotime($today)));
        $query = $this->registrar_query('12F');
        $session = new ucla_session_ext($query, $today);
        $session->update();

        $this->assertEquals('<div class="weeks-display label-fall">Fall 2012</div>',
                get_config('local_ucla', 'current_week_display'));
        $this->assertEquals('-1', get_config('local_ucla', 'current_week'));
        $this->assertEquals('12F,13W,13S,131',
                get_config('local_ucla', 'active_terms'));
        $this->assertEquals('12F', get_config('', 'currentterm'));
    }

    /**
     * Test Winter 2013 session and rolloever into Spring quarter.
     */
    public function test_winter_2013() {

        // Winter 2013 session start.
        $today = '2013-01-02';

        // Test ability to set term.
        block_ucla_weeksdisplay::init_currentterm($today);
        $this->assertEquals('13W', get_config('', 'currentterm'));

        // At start of session.
        $query = $this->registrar_query('13W');
        $session = new ucla_session_ext($query, $today);
        $session->update();

        // Session has started, but instruction hasn't yet.
        $this->assertEquals('<div class="weeks-display label-winter">Winter 2013</div>',
                get_config('local_ucla', 'current_week_display'));
        $this->assertEquals('-1', get_config('local_ucla', 'current_week'));
        $this->assertEquals('13W,13S,131,13F',
                get_config('local_ucla', 'active_terms'));

        // Winter 2013 instruction start.
        $today = '2013-01-07';
        $session->update_today($today);
        $session->update();

        $this->assertEquals('<div class="weeks-display label-winter">' .
                '<span class="session ">Winter 2013 - </span>' .
                '<span class="week">Week 1</span></div>',
                get_config('local_ucla', 'current_week_display'));
        $this->assertEquals('1', get_config('local_ucla', 'current_week'));
        $this->assertEquals('13W,13S,131,13F',
                get_config('local_ucla', 'active_terms'));

        // Check Week 10.
        $today = date('Y-m-d', strtotime('+9 weeks', strtotime($today)));
        $session->update_today($today);
        $session->update();

        $this->assertEquals('<div class="weeks-display label-winter">' .
                '<span class="session ">Winter 2013 - </span>' .
                '<span class="week">Week 10</span></div>',
                get_config('local_ucla', 'current_week_display'));
        $this->assertEquals('10', get_config('local_ucla', 'current_week'));
        $this->assertEquals('13W,13S,131,13F',
                get_config('local_ucla', 'active_terms'));

        // Check Finals week.
        $today = date('Y-m-d', strtotime('+1 week', strtotime($today)));
        $session->update_today($today);
        $session->update();

        // Sanity check.
        $this->assertEquals('Monday March 18', date('l F j', strtotime($today)));
        $this->assertEquals('<div class="weeks-display label-winter">' .
                '<span class="session ">Winter 2013 - </span>' .
                '<span class="week">Finals week</span></div>',
                get_config('local_ucla', 'current_week_display'));
        $this->assertEquals('11', get_config('local_ucla', 'current_week'));
        $this->assertEquals('13W,13S,131,13F',
                get_config('local_ucla', 'active_terms'));

        // Check Spring rollover.
        $today = date('Y-m-d', strtotime('+1 week', strtotime($today)));
        $session->update_today($today);
        $session->update();

        $this->assertEquals('13W,13S,131,13F',
                get_config('local_ucla', 'active_terms'));
        $this->assertEquals('13S', get_config('', 'currentterm'));

        unset($session);

        // Make sure Spring happens.
        $today = date('Y-m-d', strtotime('+1 hour', strtotime($today)));
        $query = $this->registrar_query('13S');
        $session = new ucla_session_ext($query, $today);
        $session->update();

        $this->assertEquals('<div class="weeks-display label-spring">Spring 2013</div>',
                get_config('local_ucla', 'current_week_display'));
        $this->assertEquals('-1', get_config('local_ucla', 'current_week'));
        $this->assertEquals('13S,131,13F,14W',
                get_config('local_ucla', 'active_terms'));
        $this->assertEquals('13S', get_config('', 'currentterm'));
    }

}
