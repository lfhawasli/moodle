@ucla @block_ucla_tasites @build_ta_site
Feature: Build TA Site
    In order to have a site in which I can manage
    As a TA
    I want to be able to create a TA site

Background:
   Given I am in a ucla environment
   And the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@asd.com |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
      | ta2 | TA | 2 | ta2@asd.com |
      | ta1 | TA | 1 | ta1@asd.com |
   And the following ucla "sites" exist:
      | fullname | shortname | type |
      | Test course 1 | C1 | srs |
   And the following ucla "enrollments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | ta2 | C1 | ta_admin |
      | ta1 | C1 | ta |
      | student1 | C1 | student |

Scenario: Build ta site as TA admin   
    Given I log in as "ta2"
    And I follow "Test course 1"
    And I press "Control Panel"
    When I follow "TA sites"
    And I press "Create TA site"
    And I set the field "Create TA site for 2, TA" to "1"
    And I press "Save changes"
    And I press "Yes"
    Then I should see "was successfully built"

Scenario: Build ta site as TA
    Given I log in as "ta1"
    And I follow "Test course 1"
    And I press "Control Panel"
    When I follow "TA sites"
    And I press "Create TA site"
    And I set the field "Create TA site for 1, TA" to "1"
    And I press "Save changes"
    And I press "Yes"
    Then I should see "was successfully built"