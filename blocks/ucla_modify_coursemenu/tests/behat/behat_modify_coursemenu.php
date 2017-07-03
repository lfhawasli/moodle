<?php
// This file is part of UCLA Modify Coursemenu plugin for Moodle - http://moodle.org/
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
 * Behat UCLA related steps definitions.
 *
 * @package    block_ucla_modify_coursemenu
 * @category   test
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/../../../../local/ucla/tests/behat/behat_ucla.php');

use Behat\Behat\Context\Step\Given as Given,
    Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Mink\Exception\ExpectationException as ExpectationException,
    Behat\Mink\Exception\DriverException as DriverException,
    Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;

/**
 * Just an extension of behat_base to include a function which returns some new behat steps.
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_modify_coursemenu extends behat_base {
    /**
     * Verifies user is on a given section.
     *
     * @Then /^I should be on section "([^"]*)"$/
     * @param string $section
     * @return array of two behat givens
     */
    public function i_should_be_on_section($section) {
        $givens = array(
            new Given('I should see "' . $section . '" in the "li.current_branch" "css_element"'),
            // Look for highlighting in the menu block.
            new Given('I should see "' . $section . '" highlighted in the ucla site menu')
        );
        return $givens;
    }

}