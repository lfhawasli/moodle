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
 * Unit tests for the front page weeks display.
 *
 * @package    theme_uclashared_weeksdisplay
 * @category   test
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/theme/uclashared/lib.php');

/**
 * Unit tests for {@link theme_uclashared_weeksdisplay_session}
 *
 * @group theme_uclashared_weeksdisplay
 * @category   test
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_uclashared_weeksdisplay_testcase extends advanced_testcase {
    
    /**
     * Tests for the correcness of parse weeks display rendered html.
     */
    public function test_parsed_weeks_display() {
        // Overriding setUp() function to always reset after tests.
        $this->resetAfterTest(true);

        // Test case for regular weeks display.
        set_config('current_week_display', '<div class="weeks-display label-winter"><span class="session">Winter 2015</span><span class="week">Week 1</span></div>', 'local_ucla');

        $output = theme_uclashared_parsed_weeks_display();
        $this->assertEquals('Winter 2015', $output[0]);
        $this->assertEquals('Week 1', $output[1]);

        // Test case for no overlap summer sessions weeks display.
        set_config('current_week_display', '<div class="weeks-display label-summer"><span class="session">Summer 2016 - Session C</span><span class="week">Week 5</span></div>', 'local_ucla');

        $output = theme_uclashared_parsed_weeks_display();
        $this->assertEquals('Summer 2016', $output[0]);
        $this->assertEquals('Session C - Week 5', $output[1]);

        // Test case for overlap summer sessions weeks display.
        set_config('current_week_display', '<div class="weeks-display label-summer"><span class="session">' .
                'Summer 2016 - Session A</span><span class="week">Week 10</span> | ' .
                '<span class="session">Summer 2016 - Session C</span><span class="week">Week 4</span></div>', 'local_ucla');

        $output = theme_uclashared_parsed_weeks_display();
        $this->assertEquals('Summer 2016', $output[0]);
        $this->assertEquals('Session A - Week 10 <br/> Session C - Week 4', $output[1]);
    }
}
