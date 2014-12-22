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

/**
 * Unit tests for {@link block_ucla_weeksdisplay_session}
 * 
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
            '14F' => array(
                array(
                    'term' => '14F',
                    'session' => 'RG',
                    'session_start' => '2014-09-29 00:00:00.000',
                    'session_end' => '2014-12-19 00:00:00.000',
                    'instruction_start' => '2014-10-02 00:00:00.000',
                    'term_start' => '2014-07-07 00:00:00.000',
                    'term_end' => '2014-12-23 00:00:00.000'
                ),
            ),
            '15W' => array(
                array(
                    'term' => '15W',
                    'session' => 'RG',
                    'session_start' => '2015-01-02 00:00:00',
                    'session_end' => '2015-03-20 00:00:00',
                    'instruction_start' => '2015-01-05 00:00:00',
                    'term_start' => '2014-11-08 00:00:00',
                    'term_end' => '2015-03-27 00:00:00'
                ),
            ),
            '15S' => array(
                array(
                    'term' => '15S',
                    'session' => 'RG',
                    'session_start' => '2015-03-25 00:00:00.000',
                    'session_end' => '2015-06-12 00:00:00.000',
                    'instruction_start' => '2015-03-30 00:00:00.000',
                    'term_start' => '2015-01-05 00:00:00.000',
                    'term_end' => '2015-06-20 00:00:00.000'
                ),
            ),
            '151' => array(
                array(
                    'term' => '151',
                    'session' => '6A',
                    'session_start' => '2015-06-22 00:00:00.000',
                    'session_end' => '2015-07-31 00:00:00.000',
                    'instruction_start' => '2015-06-22 00:00:00.000',
                    'term_start' => '2015-06-22 00:00:00.000',
                    'term_end' => '2015-09-11 00:00:00.000'
                ),
                array(
                    'term' => '151',
                    'session' => '6C',
                    'session_start' => '2015-08-03 00:00:00.000',
                    'session_end' => '2015-09-11 00:00:00.000',
                    'instruction_start' => '2015-08-03 00:00:00.000',
                    'term_start' => '2015-06-22 00:00:00.000',
                    'term_end' => '2015-09-11 00:00:00.000'
                ),
                array(
                    'term' => '151',
                    'session' => '8A',
                    'session_start' => '2015-06-22 00:00:00.000',
                    'session_end' => '2015-08-14 00:00:00.000',
                    'instruction_start' => '2015-06-22 00:00:00.000',
                    'term_start' => '2015-06-22 00:00:00.000',
                    'term_end' => '2015-09-11 00:00:00.000'
                ),
            )
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
     * Basic checks for correctness and some edge case conditions.
     * 
     */
    function test_basic_sanity() {
        
        $query = $this->registrar_query('14F');
        
        $fallsession = \block_ucla_weeksdisplay_session::create($query);
        
        $this->assertEquals(11, $fallsession->weeks_in_session());
                
        // Monday on week 0
        $today = new DateTime('2014-09-29');
        $fallsession->set_today($today);
        
        // Check for week 0
        $this->assertEquals(0, $fallsession->current_week());
        
        // Should have error if we check day before
        $today->modify('-1 day');
        $fallsession->set_today($today);
        $this->assertEquals(\block_ucla_weeksdisplay_session::WEEK_BETWEEN_SESSION, $fallsession->current_week());
        
        $this->assertEquals(false, $fallsession->in_session());
        $this->assertEquals(false, $fallsession->session_started());
        
        // Check sunday of week 4
        $today->modify('2014-11-02');
        $fallsession->set_today($today);
        $this->assertEquals(4, $fallsession->current_week());
        
        // Check following monday, should be week 5
        $today->modify('+1 day');
        $fallsession->set_today($today);
        $this->assertEquals(5, $fallsession->current_week());
        
        // Start back at week 0, then check week 1
        $today->modify('2014-09-29');
        $today->modify('+1 week');
        $fallsession->set_today($today);
        $this->assertEquals(1, $fallsession->current_week());
        
        // Check 10th week.
        $today->modify('+9 weeks');
        $fallsession->set_today($today);
        $this->assertEquals(10, $fallsession->current_week());
        
        // Check final's week.
        $today->modify('+1 week');
        $fallsession->set_today($today);
        $this->assertEquals(true, $fallsession->in_session());
        $this->assertEquals(\block_ucla_weeksdisplay_session::WEEK_FINALS, $fallsession->current_week());
        
        // Check that we are no longer in session a week after finals.
        $today->modify('+1 week');
        $fallsession->set_today($today);
        $this->assertEquals(\block_ucla_weeksdisplay_session::WEEK_BETWEEN_SESSION, $fallsession->current_week());
        $this->assertEquals(false, $fallsession->in_session());
        $this->assertEquals(false, $fallsession->term_ended());
        
        // Check that term ended
        $today->modify('+1 week');
        $fallsession->set_today($today);
        $this->assertEquals(true, $fallsession->term_ended());
        
        // One more week and we should be outside the term dates -- generates an error.
        $today->modify('+1 week');
        $fallsession->set_today($today);
        $this->assertEquals(\block_ucla_weeksdisplay_session::WEEK_ERR, $fallsession->current_week());
        
        // --------------------------------------------------------------------
        // Skip to winter.
        $nextterm = $fallsession->next_term();
        $this->assertEquals('15W', $nextterm);
        
        // Query winter
        $query = $this->registrar_query($nextterm);
        $winter = \block_ucla_weeksdisplay_session::create($query);
        $this->assertEquals(11, $winter->weeks_in_session());
        
        // Set Monday on week 0 of Fall term
        $today->modify('2014-09-29');
        $winter->set_today($today);
        $this->assertEquals(\block_ucla_weeksdisplay_session::WEEK_ERR, $winter->current_week());
        
        
        // First day of Winter instruction
        $today->modify($query[0]['instruction_start']);
        $winter->set_today($today);
        $this->assertEquals(1, $winter->current_week());
        
        // Finals week
        $today->modify('+10 weeks');
        $winter->set_today($today);
        $this->assertEquals(\block_ucla_weeksdisplay_session::WEEK_FINALS, $winter->current_week());
        
    }
    
    /**
     * Tests that configs are set accordingly.
     * 
     * @global type $CFG
     */
    function test_config_save() {
        global $CFG;
        
        $query = $this->registrar_query('14F');
        $fall = \block_ucla_weeksdisplay_session::create($query);
        
        // Assume current term is already set:
        set_config('currentterm', '14F');
        
        // Monday on week 0
        $today = new DateTime('2014-09-29');
        $fall->set_today($today);
        $fall->save_configs();
        
        // Make sure proper configs were set.
        $this->assertEquals(0, get_config('local_ucla', 'current_week'));
        $this->assertEquals('14F', $CFG->currentterm);
        
        $today->modify('+11 weeks');
        $fall->set_today($today);
        $fall->save_configs();
        
        $this->assertEquals(\block_ucla_weeksdisplay_session::WEEK_FINALS, get_config('local_ucla', 'current_week'));
        $this->assertEquals('14F', $CFG->currentterm);
        
        $today->modify('+1 week');
        $fall->set_today($today);
        $fall->save_configs();
        
        $this->assertEquals(\block_ucla_weeksdisplay_session::WEEK_BETWEEN_SESSION, get_config('local_ucla', 'current_week'));
        $this->assertEquals('14F', $CFG->currentterm);
        
        // Check that we can rollover to winter.
        // Start at Fall 14 term_end
        $today->modify($query[0]['term_end']);
        $fall->set_today($today);
        $fall->save_configs();
        
        $this->assertEquals(\block_ucla_weeksdisplay_session::WEEK_BETWEEN_SESSION, get_config('local_ucla', 'current_week'));
        $this->assertEquals('14F', $CFG->currentterm);
        
        $today->modify('+1 day');
        $fall->set_today($today);
        $fall->save_configs();
        
        $this->assertEquals(\block_ucla_weeksdisplay_session::WEEK_BETWEEN_SESSION, get_config('local_ucla', 'current_week'));
        $this->assertEquals('15W', $CFG->currentterm);
        $this->assertEquals('14F,15W,15S,151', get_config('local_ucla', 'active_terms'));
        
        // Now query with new currentterm '15W'
        $query = $this->registrar_query($CFG->currentterm);
        $winter = \block_ucla_weeksdisplay_session::create($query);
        $winter->set_today($today);
        $winter->save_configs();
        
        $this->assertEquals(false, $winter->in_session());
        $this->assertEquals(\block_ucla_weeksdisplay_session::WEEK_BETWEEN_SESSION, get_config('local_ucla', 'current_week'));
        $this->assertEquals('15W', $CFG->currentterm);

        // Set the day after
        $today->modify('+1 day');
        $winter->set_today($today);
        $winter->save_configs();
                
        $this->assertEquals(false, $winter->in_session());
        $this->assertEquals(\block_ucla_weeksdisplay_session::WEEK_BETWEEN_SESSION, get_config('local_ucla', 'current_week'));
        $this->assertEquals('15W', $CFG->currentterm);

        // Check we're at week 1 when instruction starts
        $today->modify($query[0]['instruction_start']);
        $winter->set_today($today);
        $winter->save_configs();
//        
        $this->assertEquals(1, get_config('local_ucla', 'current_week'));
        $this->assertEquals('15W', $CFG->currentterm);
    }
    
    /**
     * Tests for the correcness of weeks display rendered html
     */
    function test_week_display_save() {

        $query = $this->registrar_query('14F');
        $fall = \block_ucla_weeksdisplay_session::create($query);
        
        // Assume current term is already set:
        set_config('currentterm', '14F');
        
        // Monday on week 0
        $today = new DateTime('2014-09-29');
        $fall->set_today($today);
        $fall->update_week_display();
        
        $this->assertEquals('<div class="weeks-display label-fall"><span class="session">Fall 2014</span><span class="week">Week 0</span></div>', get_config('local_ucla', 'current_week_display'));
        
        $today->modify('+11 weeks');
        $fall->set_today($today);
        $fall->update_week_display();
        
        $this->assertEquals('<div class="weeks-display label-fall"><span class="session">Fall 2014</span><span class="week">Finals week</span></div>', get_config('local_ucla', 'current_week_display'));
        
        $today->modify('+1 week');
        $fall->set_today($today);
        $fall->update_week_display();
        
        $this->assertEquals('<div class="weeks-display label-fall"><span class="session">Fall 2014</span></div>', get_config('local_ucla', 'current_week_display'));
        
        // Check Winter roll over
        $query = $this->registrar_query('15W');
        $winter = \block_ucla_weeksdisplay_session::create($query);

        // First check winter break
        $today->modify('2014-12-24');
        $winter->set_today($today);
        $winter->update_week_display();
        
        $this->assertEquals('<div class="weeks-display label-winter"><span class="session">Winter 2015</span><span class="week">Winter break</span></div>', get_config('local_ucla', 'current_week_display'));
        
        $today->modify($query[0]['instruction_start']);
        $today->modify('-1 day');
        $winter->set_today($today);
        $winter->update_week_display();
        $this->assertEquals('<div class="weeks-display label-winter"><span class="session">Winter 2015</span><span class="week">Winter break</span></div>', get_config('local_ucla', 'current_week_display'));
        
        // Check we're at week 1 when instruction starts
        $today->modify($query[0]['instruction_start']);
        $winter->set_today($today);
        $winter->update_week_display();
        
        $this->assertEquals('<div class="weeks-display label-winter"><span class="session">Winter 2015</span><span class="week">Week 1</span></div>', get_config('local_ucla', 'current_week_display'));
        
        // Check finals week.
        $today->modify('+10 weeks');
        $winter->set_today($today);
        $winter->update_week_display();
        
        $this->assertEquals('<div class="weeks-display label-winter"><span class="session">Winter 2015</span><span class="week">Finals week</span></div>', get_config('local_ucla', 'current_week_display'));
    }
    
    /**
     * Tests that Monday - Sunday remain same week.
     */
    function test_session_monday_start() {

        // Get Fall
        $query = $this->registrar_query('14F');
        $fall = \block_ucla_weeksdisplay_session::create($query);
        
        $today = new DateTime('2014-09-29');
        $fall->set_today($today);
        $this->assertEquals(0, $fall->current_week());
        
        $today = new DateTime('2014-10-05');
        $fall->set_today($today);
        $this->assertEquals(0, $fall->current_week());
        
        $today->modify('+1 day');
        $fall->set_today($today);
        $this->assertEquals(1, $fall->current_week());
        
        $today->modify('+6 days');
        $fall->set_today($today);
        $this->assertEquals(1, $fall->current_week());
        
        // Get winter
        $query = $this->registrar_query('15W');
        $winter = \block_ucla_weeksdisplay_session::create($query);
         
        // Start on Monday of week 1
        $today = new DateTime('2015-01-05');
        $winter->set_today($today);
        $this->assertEquals(1, $winter->current_week());
        
        // Check on Sunday of week 1
        $today = new DateTime('2015-01-11');
        $winter->set_today($today);
        $this->assertEquals(1, $winter->current_week());

    }
    
    /**
     * Tests summer sessions
     */
    function test_summer() {
        
        $query = $this->registrar_query('151');
        $summer = \block_ucla_weeksdisplay_session::create($query);
        
        $today = new DateTime($query[0]['session_start']);
        $summer->set_today($today);
        $summer->update_week_display();
        
        
        
    }
}