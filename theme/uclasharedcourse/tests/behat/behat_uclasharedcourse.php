<?php
// This file is part of the UCLA shared course theme for Moodle - http://moodle.org/
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
 * Behat theme_uclasharedcourse helper code.
 *
 * @package    theme_uclasharedcourse
 * @category   test
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');
use Behat\Behat\Context\Step\Then as Then,
    Behat\Mink\Exception\ExpectationException as ExpectationException,
    Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;

/**
 * Steps definitions related with custom logos for sites.
 *
 * @package    theme_uclasharedcourse
 * @category   test
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_uclasharedcourse extends behat_base {

    /**
     * Checks that the specified element contains an image containing
     * the specified file.
     *
     * @Then /^I should see image "([^"]*)" in the "(?P<element_string>(?:[^"]|\\")*)" "(?P<text_selector_string>[^"]*)"$/
     * @throws ElementNotFoundException
     * @param string $image
     * @param string $element Element we look in.
     * @param string $selectortype The type of element where we are looking in.
     */
    public function i_should_see_custom_logo($image, $element, $selectortype) {

        // Getting the container where the image should be found.
        $container = $this->get_selected_node($selectortype, $element);
        $html = $container->getHtml();

        if (empty($html) || core_text::strpos($html, $image) === false) {
            throw new ElementNotFoundException($this->getSession(), "$image image ");
        }
        return;
    }

    /**
     * Checks that logo on page is from given directory.
     *
     * @Then /^I should not see image "([^"]*)" in the "(?P<element_string>(?:[^"]|\\")*)" "(?P<text_selector_string>[^"]*)"$/
     * @throws ExpectationException
     * @param string $image
     * @param string $element Element we look in.
     * @param string $selectortype The type of element where we are looking in.
     *
     */
    public function i_should_not_see_custom_logo($image, $element, $selectortype) {

        try {
            // Getting the container where the image should not be found.
            $container = $this->get_selected_node($selectortype, $element);
        } catch (ElementNotFoundException $notfound) {
            // If not found, then image doesn't exist.
            return;
        }
        $html = $container->getHtml();
        if (!empty($html) && core_text::strpos($html, $image) !== false) {
            throw new ExpectationException("Found $image image", $this->getSession());
        }

    }

}
