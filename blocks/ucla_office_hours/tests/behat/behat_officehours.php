<?php

/**
 * Behat office hours-related steps definitions.
 * 
 * @package    block_ucla_office_hours
 * @category   test
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException as ExpectationException;

class behat_officehours extends behat_base {

   /**
     * Updates office hours for given user.
     * 
     * @Given /^I update office hours for "([^"]*)"$/
     */
    public function i_update_office_hours_for($user) {
        $exception = new ExpectationException('Unable edit office hours for user: "' . $user . '"', $this->getSession());
        $updateLink = $this->find('xpath','//td[contains(text(),"'.$user.'")]/preceding-sibling::td//a[@title="'.get_string('update', 'block_ucla_office_hours').'"]', $exception);
        $updateLink->click();
    }
}
