<?php
/**
 * Behat UCLA related steps definitions.
 *
 * @package    local_publicprivate
 * @category   test
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/../../../../lib/tests/behat/behat_data_generators.php');

use Behat\Behat\Context\Step\Given as Given,
    Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Mink\Exception\ExpectationException as ExpectationException,
    Behat\Mink\Exception\DriverException as DriverException,
    Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;
    

class behat_publicprivate extends behat_base {
        
    /**
     * Step to test if an activity is private.
     * 
     * @Then /^"([^"]*)" activity should be private$/
     */
    public function activity_should_be_private($activityname) {
        $activitynode = $this->get_activity_node($activityname);

        // Should have "Private Course Material" grouping label.
        $exception = new ExpectationException('"' . $activityname . '" is not private', $this->getSession());
        $grouping = $this->find('css', 'span.groupinglabel', $exception, $activitynode);
        
        if ($grouping->getText() != "(Private Course Material)") {
            throw $exception;
        }
    }

    /**
     * Step to test if activity is public.
     * 
     * @Then /^"([^"]*)" activity should be public$/
     */
    public function activity_should_be_public($activityname) {
        $activitynode = $this->get_activity_node($activityname);

        // Should not have a grouping label.
        try {
            $this->find('css', 'span.groupinglabel', false, $activitynode);
            throw new ExpectationException('"' . $activityname . '" is not public', $this->getSession());
        } catch (ElementNotFoundException $e) {
            // All ok.
        }
    }

    /**
     * Returns the DOM node of the activity from <li>.
     *
     * Copied from course/tests/behat/behat_course.php.
     *
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $activityname The activity name
     * @return NodeElement
     */
    protected function get_activity_node($activityname) {

        $activityname = $this->getSession()->getSelectorsHandler()->xpathLiteral($activityname);
        $xpath = "//li[contains(concat(' ', normalize-space(@class), ' '), ' activity ')][contains(., $activityname)]";

        return $this->find('xpath', $xpath);
    }

    /**
     * Make course module private.
     * 
     * @Given /^I make "([^"]*)" private$/
     * @param string $activityname
     */
    public function i_make_private($activityname)
    {
        $steps = array(
            new Given('I click on "' . get_string('publicprivatemakeprivate', 'local_publicprivate') . '" "link" in the "' . $this->escape($activityname) . '" activity')
        );

        if ($this->running_javascript()) {
            $steps[] = new Given('I wait "2" seconds');
        }

        return $steps;
    }

    /**
     * Make course module public.
     *
     * @Given /^I make "([^"]*)" public$/
     * @param string $activityname
     */
    public function i_make_public($activityname)
    {
        $steps = array(
            new Given('I click on "' . get_string('publicprivatemakepublic', 'local_publicprivate') . '" "link" in the "' . $this->escape($activityname) . '" activity')
        );

        if ($this->running_javascript()) {
            $steps[] = new Given('I wait "2" seconds');
        }

        return $steps;
    }
}
