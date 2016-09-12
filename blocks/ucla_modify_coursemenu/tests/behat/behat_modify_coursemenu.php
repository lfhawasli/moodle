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
require_once(__DIR__ . '/../../../../local/ucla/tests/behat/behat_ucla.php');

use Behat\Behat\Context\Step\Given as Given,
    Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Mink\Exception\ExpectationException as ExpectationException,
    Behat\Mink\Exception\DriverException as DriverException,
    Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;
    

class behat_modify_coursemenu extends behat_base {
          
    /**
     * @Then /^I should be on section "([^"]*)"$/
     */
    public function i_should_be_on_section($section) {
        
        $givens = array(
            new Given('I should see "' . $section . '" in the "li.current_branch" "css_element"'),
            // look for highlighting in the menu block
            new Given('I should see "' . $section . '" highlighted in the ucla site menu')
        );

        return $givens;
        
    }

}
