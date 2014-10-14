@ucla @local_ucla @theme_uclasharedcourse @CCLE-4863
Feature: FERPA waiver
    In order to notify my data is being sent to a 3rd party
    As a student in a collaboration site with a custom theme
    I need to be prompted that my data is will be transferred out of CCLE and agree to it

Background:
    Given I am in a ucla environment
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher@asd.com |
      | student1 | Student | 1 | student@asd.com |
   And the following ucla "sites" exist:
      | fullname | shortname | type |
      | Course 1 | C1 | instruction |
    And the following ucla "enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editinginstructor |
      | student1 | C1 | student |
    And I log in as "admin"
    And I follow "Course 1"
    And I follow "Edit settings"
    And I expand all fieldsets
    And I set the field "Force theme" to "UCLA course theme"
    And I press "Save changes"

Scenario: Require students to sign waiver for LTI
    Given I turn editing mode on
    And I add a "External Tool" to section "0" and I fill the form with:
        | Activity Name | LTI |
    And I log out
    When I log in as "student1"
    And I follow "Course 1"
    And I follow "LTI"
    Then I should see "Privacy Waiver for LTI"
    And I should see "Before you may continue you must agree to the following statements."
    When I press "I agree"
    Then I should see "LTI"

Scenario: Do not require non-students to sign waiver for LTI
    Given I turn editing mode on
    And I add a "External Tool" to section "0" and I fill the form with:
        | Activity Name | LTI |
    And I follow "LTI"
    Then I should not see "Privacy Waiver for LTI"
    And I should not see "Before you may continue you must agree to the following statements."
    And I should see "LTI"
