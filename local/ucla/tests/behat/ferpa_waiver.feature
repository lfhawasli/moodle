@ucla @local_ucla @mod_lti @mod_casa @CCLE-4863
Feature: FERPA waiver
    In order to notify my data is being sent to a 3rd party
    As a student
    I need to be prompted that my data is will be transferred out of CCLE and agree to it

Background:
    Given the following "users" exist:
        | username | firstname | lastname | email           |
        | teacher1 | Teacher   | 1        | teacher@asd.com |
        | student1 | Student   | 1        | student@asd.com |
   And the following "courses" exist:
        | fullname | shortname |
        | Course 1 | C1        |
    And the following "course enrolments" exist:
        | user     | course | role           |
        | teacher1 | C1     | editingteacher |
        | student1 | C1     | student        |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage

Scenario: Require students to sign waiver for LTI.
    Given I turn editing mode on
    And I add a "External tool" to section "0" and I fill the form with:
        | Activity name        | LTI                          |
        | Launch/cartridge URL | http://lti.tools/test/tp.php |
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "LTI"
    Then I should see "Privacy Waiver"
    And I should see "By continuing to LTI, you will be sharing"
    When I press "I agree"
    Then I should see "LTI"

@javascript @_switch_window @CCLE-6534
Scenario: Require students to sign waiver for LTI for resources that open in new window.
    Given I turn editing mode on
    And I add a "External tool" to section "0" and I fill the form with:
        | Activity name        | LTI                          |
        | Launch/cartridge URL | http://lti.tools/test/tp.php |
        | Launch container     | New window                   |
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "LTI"
    And I switch to "lti" window
    Then I should see "Privacy Waiver"
    And I should see "By continuing to LTI, you will be sharing"
    When I press "I agree"
    Then I should see "IMS LTI tool provider emulator"

Scenario: Allow students to not agree.
    Given I turn editing mode on
    And I add a "External tool" to section "0" and I fill the form with:
        | Activity name | LTI |
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "LTI"
    Then I should see "Privacy Waiver"
    And I should see "By continuing to LTI, you will be sharing"
    When I press "I do not agree"
    Then I should see "Please talk to your instructor about what options are available to you."

Scenario: Do not require non-students to sign waiver for LTI.
    Given I turn editing mode on
    And I add a "External tool" to section "0" and I fill the form with:
        | Activity name | LTI |
    And I follow "LTI"
    Then I should not see "Privacy Waiver"
    And I should not see "By continuing to LTI, you will be sharing"
    And I should see "LTI"

Scenario: Do not provide waiver for UCLA LTI resources.
    Given I turn editing mode on
    And I add a "External tool" to section "0" and I fill the form with:
        | Activity name        | LTI                         |
        | Launch/cartridge URL | https://d3.sscnet.ucla.edu/ |
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "LTI"
    Then I should not see "Privacy Waiver"
    And I should not see "By continuing to LTI, you will be sharing"
    And I should see "LTI"

@javascript
Scenario: Do not provide waiver for site configured LTI resources.
    Given I log out
    And I log in as "admin"
    And I navigate to "Manage tools" node in "Site administration > Plugins > Activity modules > External tool"
    And I follow "configure a tool manually"
    And I set the field "Tool name" to "Site configured LTI"
    And I set the field "Tool base URL/cartridge URL" to "http://lti.tools/test/tp.php"
    And I set the field "Consumer key" to "jisc.ac.uk"
    And I set the field "Shared secret" to "secret"
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "External tool" to section "0" and I fill the form with:
        | Activity name        | Site LTI                     |
        | Launch/cartridge URL | http://lti.tools/test/tp.php |
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Site LTI"
    Then I should not see "Privacy Waiver"
    And I should not see "By continuing to Site LTI, you will be sharing"
    And I should see "Site LTI"

@javascript
Scenario: Do not provide waiver for site pre-configured LTI resources.
    Given I log out
    And I log in as "admin"
    And I navigate to "Manage tools" node in "Site administration > Plugins > Activity modules > External tool"
    And I follow "configure a tool manually"
    And I set the field "Tool name" to "Site configured LTI"
    And I set the field "Tool base URL/cartridge URL" to "http://lti.tools/test/tp.php"
    And I set the field "Consumer key" to "jisc.ac.uk"
    And I set the field "Shared secret" to "secret"
    And I set the field "Tool configuration usage" to "Show as preconfigured tool when adding an external tool"
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "External tool" to section "0" and I fill the form with:
        | Activity name      | Site LTI            |
        | Preconfigured tool | Site configured LTI |
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Site LTI"
    Then I should not see "Privacy Waiver"
    And I should not see "By continuing to Site LTI, you will be sharing"
    And I should see "Site LTI"
