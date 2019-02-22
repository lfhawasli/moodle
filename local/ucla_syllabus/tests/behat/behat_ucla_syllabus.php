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
 * Behat UCLA Syllabus related steps definitions.
 *
 * @package    local_ucla_syllabus
 * @category   test
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// NOTE: No MOODLE_INTERNAL test here, this file may be required by behat before
// including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Behat\Context\Step\Given as Given,
    Behat\Behat\Context\Step\When as When,
    Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Mink\Exception\ExpectationException as ExpectationException,
    Behat\Mink\Exception\DriverException as DriverException,
    Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;

/**
 * Behat custom steps.
 *
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_ucla_syllabus extends behat_base {

    /**
     * Step to add a public syllabus.  This uses an empty file.
     *
     * @Given /^I add a new public syllabus$/
     */
    public function i_add_a_new_public_syllabus() {
        return array(
            new Given('I follow "Syllabus (empty)"'),
            new Given('I follow "Add syllabus"'),
            new Given('I upload "lib/tests/fixtures/empty.txt" file to "File" filemanager'),
            new Given('I press "Save changes"')
        );
    }

    /**
     * Step to delete a public syllabus. Assumes you are in syllabus editing mode.
     *
     * @When /^I delete a public syllabus$/
     */
    public function i_delete_a_public_syllabus() {

        // Click on the delete link.
        $linknode = $this->find_link('delete-public-syllabus');
        $linknode->click();

        if ($this->running_javascript()) {
            // Click YES on the javascript alert.
            $this->getSession()->getDriver()->getWebDriverSession()->accept_alert();
            // Wait..
            $this->getSession()->wait(2 * 1000, false);

        }
    }

    /**
     * Step directly view syllabus. Useful for when a course is hidden and
     * students don't have link to course from their My sites.
     *
     * @When /^I view syllabus for "([^"]*)"$/
     */
    public function i_view_syllabus($fullname) {
        print_object($fullname);
        global $DB;
        // Try to find course fullname.
        $course = $DB->get_record('course', array('fullname' => $fullname));
        if (empty($course)) {
            throw new ExpectationException('Course not found for fullname ' . $fullname,
                    $this->getSession());
        }
        $url = new moodle_url('/local/ucla_syllabus/index.php', array('id' => $course->id));
        $this->getSession()->visit($this->locate_path($url->out_as_local_url()));
    }
}