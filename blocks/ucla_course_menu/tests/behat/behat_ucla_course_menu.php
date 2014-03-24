<?php
// This file is part of the UCLA site menu block for Moodle - http://moodle.org/
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
 * Behat UCLA related steps definitions for site menu block.
 *
 * @package    block_ucla_course_menu
 * @category   test
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/../../../../local/ucla/tests/behat/behat_ucla.php');

use Behat\Behat\Context\Step\When as When;

/**
 * Behat step class.
 *
 * @package    block_ucla_course_menu
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_ucla_course_menu extends behat_base {

    /**
     * Shortcut for clicking links in the UCLA site menu block.
     *
     * @When /^I follow the "([^"]*)" section in the ucla site menu$/
     *
     * @param string $section
     */
    public function i_follow_site_menu_section($section) {
        return array(
            new When('I click on "' . $section . '" "link" in the ".block_ucla_course_menu" "css_element"')
        );
    }

}
