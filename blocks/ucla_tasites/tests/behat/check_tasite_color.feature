@ucla @block_ucla_tasites @tasite_color
Feature: TA site color
    In order to determine that I am in a TA site
    As a user
    I want to have the site menu change its color

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

Scenario: Check color for menu block is different
    Given I log in as "ta1"
    And I follow "Test course 1"
    And I press "Control Panel"
    When I follow "TA sites"
    And I set the field "Create TA site for 1, TA" to "1"
    And I press "Save changes"
    And I press "Yes"
    And I should see "was successfully built"
    And I follow the "Site info" section in the ucla site menu
    And I follow "View TA site"
    Then I should see "Site info" in the ".tasites" "css_element"
