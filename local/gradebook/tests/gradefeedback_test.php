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
 * Tests the filtering of text before it is sent to MyUCLA.
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
class gradefeedback_test extends basic_testcase {
    /**
     * Given a string, return true or false if it contains invalid characters,
     * which arecharacters that have a demical value of 20 or less on the ASCII
     * table, except tabs, new lines, and carriage returns.
     *
     * See http://www.asciitable.com
     *
     * @param string $string
     * @return boolean
     */
    public function has_invalid_characters($string) {
        $length = core_text::strlen($string);
        for ($i = 0; $i < $length; $i++) {
            $current = ord($string{$i});
            if (($current == 0x9) || ($current == 0xA) || ($current == 0xD) ||
                    (($current >= 0x20) && ($current <= 0xD7FF)) ||
                    (($current >= 0xE000) && ($current <= 0xFFFD)) ||
                    (($current >= 0x10000) && ($current <= 0x10FFFF))) {
                // Valid character.
                continue;
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns sample feedback strings that have invalid characters inserted
     * into them.
     * 
     * @return array
     */
    public function invalidfeedback_provider() {
        // Create 5 random string with 1 or more invalid characters.
        $retval = array();
        while (count($retval) < 5) {
            $string = $this->rand_string(rand(5, 200), true);
            if ($this->has_invalid_characters($string)) {
                $retval[] = array($string);
            }
        }
        return $retval;
    }

    /**
     * Creates a random string of characters.
     *
     * From http://www.lateralcode.com/creating-a-random-string-with-php.
     *
     * @param int $length
     * @param boolean $includeinvalidchars  If true, will seed string with
     *                                      invalid characters, which are
     *                                      characters that have a demical value
     *                                      of 20 or less on the ASCII table,
     *                                      except tabs, new lines, and carriage
     *                                      returns.
     *                                      (see http://www.asciitable.com/).
     * @return string
     */
    private function rand_string($length, $includeinvalidchars = false) {
        $str = '';
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 ";

        if ($includeinvalidchars) {
            for ($i = 1; $i <= 20; $i++) {
                if (in_array($i, array(9, 10, 13))) {
                    // Skip horizontal tab, new line, and carriage return.
                    continue;
                }
                $chars .= chr($i);
            }
        }

        $size = strlen($chars);
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[rand(0, $size - 1)];
        }

        return $str;
    }

    /**
     * Tests that HTML is filtered out.
     */
    public function test_html_filtering() {
        $html = "<h1>Hello World!</h1>\n<p>Newline.";
        $task = new \local_gradebook\task\send_myucla_grade();
        $filtered = $task->trim_and_strip($html);
        $this->assertEquals("Hello World!\nNewline.", $filtered);
    }

    /**
     * Tests the filtering of invalid characters.
     *
     * @dataProvider invalidfeedback_provider
     *
     * @param string $feedback
     */
    public function test_invalid_feedback($feedback) {
        $task = new \local_gradebook\task\send_myucla_grade();
        $feedback = $task->trim_and_strip($feedback);
        $isinvalid = $this->has_invalid_characters($feedback);
        $this->assertFalse($isinvalid);
    }

    /**
     * Tests the triming of long feedback.
     */
    public function test_long_feedback() {
        // Create super long feedback, over grade_reporter::MAX_COMMENT_LENGTH.
        $maxtextlength = \local_gradebook\task\send_myucla_grade::MAXTEXTLENGTH;
        $feedback = $this->rand_string($maxtextlength + 100);
        $feedbacklength = core_text::strlen($feedback);
        $this->assertTrue($feedbacklength > $maxtextlength);

        // Make sure feedback is trimed.
        $task = new \local_gradebook\task\send_myucla_grade();
        $processedfeedback = $task->trim_and_strip($feedback);

        $processedlength = core_text::strlen($processedfeedback);
        $this->assertLessThan($feedbacklength, $processedlength);
        $endswithellipses = core_text::substr($processedfeedback,
                -core_text::strlen(get_string('continuecomments', 'local_gradebook')))
                == get_string('continuecomments', 'local_gradebook');
        $this->assertTrue($endswithellipses);
    }

}
