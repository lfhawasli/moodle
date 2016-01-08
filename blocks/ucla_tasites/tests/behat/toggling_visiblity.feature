@ucla @block_ucla_tasites @tasite_toggling
Feature: Link to TA Site
    In order to get to a TA site
    As a user
    I want to find the link next to the TA's name in the office hours block

Background:
   Given I am in a ucla environment
   And the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@asd.com |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
      | ta1 | TA | 1 | ta1@asd.com |
   And the following ucla "sites" exist:
      | fullname | shortname | type |
      | Test course 1 | C1 | srs |
   And the following ucla "roles" exist:
      | role |
      | ta |
      | ta_admin |
   And the following ucla "enrollments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | ta1 | C1 | ta |
      | student1 | C1 | student |

Scenario: Hide and show TA site
    Given I log in as "ta1"
    And I follow "Test course 1"
    And I press "Control Panel"
    And I follow "TA sites"
    And I press "Create TA site"
    And I set the field "Create TA site for 1, TA" to "1"
    And I press "Save changes"
    And I press "Yes"
    When I click on "Hide" "link"
    Then I should see "Successfully hid TA site"
    When I click on "Show" "link"
    Then I should see "Successfully un-hid TA site"