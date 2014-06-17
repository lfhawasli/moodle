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
 * Behat UCLA related steps definitions for weeks display block.
 *
 * @package    block_ucla_weeksdisplay
 * @category   test
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Behat\Context\Step\Given as Given;

/**
 * Behat step class.
 *
 * @package    block_ucla_weeksdisplay
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_ucla_weeksdisplay extends behat_base {

    /**
     * Queries for the currentterm and current_week and sets 
     * current_week_display to the appropiate value and formatting.
     *
     * @param string $term  The 3 character term value.
     * @param int $week     Value -1 to 11.
     */
    private function set_weeksdisplay($term, $weeknum) {
        require_once(__DIR__ . '/../../block_ucla_weeksdisplay.php');

        if (empty($term) || is_null($weeknum)) {
            throw new Exception("Cannot set weeksdisplay");
        }

        block_ucla_weeksdisplay::set_term($term);
        block_ucla_weeksdisplay::set_current_week($weeknum);

        // Get nice looking term text.
        $termstring = ucla_term_to_text($term);
        $explodedterm = explode(' ', $termstring);  // Used for label later.

        // Format weekdisplay.
        $weekdisplay = '';
        if ($weeknum == -1) {
            // Do not show week.
            $weekdisplay = $termstring;
        } else {
            // Handle summer session.
            $summersession = '';
            if (is_summer_term($term)) {
                // For sake of simplicity, we are only handling session A.
                $summersession = $summersession = get_string('session',
                        'block_ucla_weeksdisplay', 'A') . ', ';
            }
            // Handle week numbers.
            $week = get_string('week', 'block_ucla_weeksdisplay', $weeknum);
            if ($weeknum == 11) {
                $week = get_string('finals_week', 'block_ucla_weeksdisplay');
            }
            $weekdisplay = html_writer::span($termstring.' - '.$summersession, 
                    'session ') . html_writer::span($week, 'week');
        }

        // Set weeks display.
        $weekdisplay = html_writer::div($weekdisplay, 'weeks-display label-' .
                strtolower($explodedterm[0]));
        block_ucla_weeksdisplay::set_week_display($weekdisplay);
    }

    /**
     * Sets the term and week according to given parameters.
     *
     * @Given /^it is "([^"]*)" term "([^"]*)" week$/
     *
     * @param string $termstring    Term in the form of "Spring 2014".
     * @param string $weekstring    We want to be able to handle nice phrases
     *                              like "9th" or "Finals" or "zero" week. If
     *                              you want to set a week between terms, then
     *                              pass "-1" week.
     */
    public function it_is_term_week($termstring, $weekstring) {
        // Convert term string "Term YYYY" into "YYT" (Year and term letter).
        list($termname, $year) = explode(' ', $termstring);
        if (empty($termname) || empty($year)) {
            throw new Exception("Cannot parse term $termstring");
        }

        $year = substr($year, 2, 2);
        $termletter = '';
        switch ($termname) {
            case "Fall":
                $termletter = "F";
                break;
            case "Winter":
                $termletter = "W";
                break;
            case "Spring":
                $termletter = "S";
                break;
            case "Summer":
                $termletter = "1";
                break;
            default:
                throw new Exception("Invalid term passed $termname");
        }
        block_ucla_weeksdisplay::set_term($year.$termletter);

        // Set week number. Will also set week display.
        $this->it_is_week($weekstring);
    }

    /**
     * Sets the weeks display block to be the given week number.
     *
     * @Given /^it is "([^"]*)" week$/
     *
     * @param string $weekstring    We want to be able to handle nice phrases
     *                              like "9th" or "Finals" or "zero" week. If
     *                              you want to set a week between terms, then
     *                              pass "-1" week.
     */
    public function it_is_week($weekstring) {
        global $CFG;
        // Check for special cases.
        $weeknum = -1;
        if (strcasecmp($weekstring, "Finals") === 0) {
            $weeknum = 11;  // Assuming it isn't summer.
        } else if (intval($weekstring) >= 0 && intval($weekstring) <= 10) {
            $weeknum = intval($weekstring);
        }

        // Set display. There should be a current term already set if in UCLA
        // environment.
        $this->set_weeksdisplay($CFG->currentterm, $weeknum);
    }
}
