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
 * Behat enrol_invitation helper code.
 *
 * @package    enrol_invitation
 * @category   test
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException as ExpectationException,
    Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;

/**
 * Steps definitions related to site invitation testing.
 *
 * @package    enrol_invitation
 * @category   test
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_enrol_invitation extends behat_base {
    /**
     * Attempts to navigate to access page that enrols user in a course.
     * Equivalent to clicking ACCESS LINK in the last invite email to the user.
     *
     * Ignore long line cording standard.
     * @codingStandardsIgnoreLine
     * @Then /^I follow the link in the last invitation sent to "(?P<username_string>(?:[^"]|\\")*)" for site "(?P<course_fullname_string>(?:[^"]|\\")*)"$/
     *
     * @param string $uname
     * @param string $fullname
     */
    public function i_follow_link_in_last_invitation_sent_to_for_site($uname, $fullname) {
        global $DB;

        $sql = "SELECT e.token
                  FROM {course} c
                  JOIN {enrol_invitation} e ON e.courseid=c.id
                  JOIN {user} u ON u.email=e.email
                 WHERE c.fullname=:fullname
                   AND u.username=:username
              ORDER BY e.id DESC";

        $params = array('fullname' => $fullname, 'username' => $uname);
        if (!($token = $DB->get_field_sql($sql, $params, IGNORE_MULTIPLE))) {
            $DB->set_debug(false);
            throw new ElementNotFoundException($this->getSession(),
                    'The user "' . $uname . '" does not have any pending invites for this course');
        }

        $inviteurl = new moodle_url('/enrol/invitation/enrol.php', array('token' => $token));
        $inviteurl = $inviteurl->out(false);

        $this->getSession()->visit($inviteurl);
    }

    /**
     * Checks that a given message was in the last invitation sent to the user.
     * Note that this step requires the recipient to have an email.
     *
     * Ignore long line cording standard.
     * @codingStandardsIgnoreLine
     * @Then /^the last invite sent to "(?P<username_string>(?:[^"]|\\")*)" should contain "(?P<expected_message_string>(?:[^"]|\\")*)"$/
     *
     * @param string $uname
     * @param string $message
     */
    public function the_last_invite_sent_to_should_contain($uname, $message) {
        global $DB;

        // Need to order by id DESC to get most recent invitation.
        // (Can't use timesent because Selenium is too fast.)
        $sql = "SELECT e.message "
                .     "FROM {enrol_invitation} e "
                .     "JOIN {user} u ON e.email = u.email "
                .    "WHERE u.username = :username "
                . "ORDER BY e.id DESC";
        $params = array('username' => $uname);
        if (!($emessage = $DB->get_field_sql($sql, $params, IGNORE_MULTIPLE))) {
            throw new ElementNotFoundException('The user "' . $uname . '" does not have a pending invitation.');
        } else if (strpos($emessage, $message) === false) {
            throw new ExpectationException('The message text "' . $message . '" was not found in the most recent invitation.');
        }
    }

}
