@ucla @block_ucla_tasites @parentsite_link
Feature: Link back to parent site
    In order to quickly get back to the main course
    As a user
    I want to have a quick link in the site menu block
    NOTE: CCLE-4672 - ta2 exists to temporarily import ta_admin role.

Background:
    Given I am in a ucla environment
    And the following "users" exist:
        | username | firstname | lastname | email |
        | student1 | Student | 1 | student1@asd.com |
        | teacher1 | Teacher | 1 | teacher1@asd.com |
        | ta1 | TA | 1 | ta1@asd.com |
        | ta2 | TA2 | 1 | ta2@asd.com |
    And the following ucla "sites" exist:
        | fullname | shortname | type |
        | Test course 1 | C1 | srs |
    And the following ucla "enrollments" exist:
        | user | course | role |
        | teacher1 | C1 | editingteacher |
        | ta1 | C1 | ta |
        | ta2 | C1 | ta_admin |
        | student1 | C1 | student |

Scenario: Check if TA site links back to parent site
    Given I log in as "ta1"
    And I follow "Test course 1"
    And I press "Control Panel"
    When I follow "TA sites"
    And I set the field "Create TA site for 1, TA" to "1"
    And I press "Save changes"
    And I press "Yes"
    Then I should see "was successfully built"
    And I follow the "Site info" section in the ucla site menu
    And I follow "View TA site"
    And I should see "Class home" in the ucla site menu
