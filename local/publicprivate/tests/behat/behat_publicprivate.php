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
     * @todo: implement in xpath?
     * @Then /^"([^"]*)" activity should be private$/
     */
    public function activity_shouldbe_private($activityname) {
        return new Given("I should see \"$activityname(Private Course Material)\"");
    }

    /**
     * Step to test if activity is public.
     * 
     * @todo: implement in xpath?
     * @Then /^"([^"]*)" activity should be public$/
     */
    public function activity_shouldbe_public($activityname) {
        return new Given("I should not see \"$activityname(Private Course Material)\"");
    }

}
