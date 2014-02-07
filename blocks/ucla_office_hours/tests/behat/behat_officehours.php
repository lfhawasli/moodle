<?php

/**
 * Behat office hours-related steps definitions.
 * 
 * @package    block_ucla_office_hours
 * @category   test
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class behat_officehours extends behat_base {

   /**
     * Updates office hours for given user.
     * 
     * @Given /^I update office hours for "([^"]*)"$/
     */
    public function i_update_office_hours_for($arg1) {
        $updateLink = $this->find('xpath','//td[contains(text(),"'.$arg1.'")]/preceding-sibling::td//a[@title="'.get_string('update', 'block_ucla_office_hours').'"]');
        $updateLink->click();
    }
}
