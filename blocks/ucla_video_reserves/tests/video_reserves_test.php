<?php
// This file is part of the UCLA video reserves block for Moodle - http://moodle.org/
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
 * Unit tests for the UCLA video reserves block.
 *
 * @package    block_ucla_video_reserves
 * @category   test
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/ucla_video_reserves/locallib.php');

/**
 * Unit tests for {@link block_ucla_video_reserves}.
 *
 * @group block_ucla_video_reserves
 */
class ucla_video_reserves_test extends basic_testcase {

    /**
     * Checks for correctness for sorting videos alphabetically.
     */
    public function test_video_sorting() {
        // Create test videos.
        $videos = array();
        $videos[0] = new stdClass();
        $videos[0]->video_title = "The Closet";
        $videos[1] = new stdClass();
        $videos[1]->video_title = "A Face";
        $videos[2] = new stdClass();
        $videos[2]->video_title = "Advertising";
        $videos[3] = new stdClass();
        $videos[3]->video_title = "Angel Eyes";
        $videos[4] = new stdClass();
        $videos[4]->video_title = "An Unknown Woman";
        $videos[5] = new stdClass();
        $videos[5]->video_title = "There Will Be Blood";
        // Sort videos.
        usort($videos, 'cmp_title');
        // Check videos are sorted correctly.
        $this->assertEquals("Advertising", $videos[0]->video_title);
        $this->assertEquals("Angel Eyes", $videos[1]->video_title);
        $this->assertEquals("The Closet", $videos[2]->video_title);
        $this->assertEquals("A Face", $videos[3]->video_title);
        $this->assertEquals("There Will Be Blood", $videos[4]->video_title);
        $this->assertEquals("An Unknown Woman", $videos[5]->video_title);
    }
}