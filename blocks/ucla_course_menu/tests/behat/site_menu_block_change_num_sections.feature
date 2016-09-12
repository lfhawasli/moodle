@ucla @block_ucla_course_menu
Feature: Change number of sections for the course
  As an instructor
  I log into a course and click "Edit settings" to modify the number of sections for the course

  Background:
    Given I am in a ucla environment
    And the following ucla "sites" exist:
      | fullname | shortname | type |
      | Course 1 | C1 | srs |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
    And the following ucla "enrollments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I browse to site "C1"

  Scenario: Modify number of sections of a course
    Given I follow "Edit settings"
    And I set the following fields to these values:
      | Number of sections | 2 |
    And I press "Save and display"
    Then I should see "Week 1" in the ucla site menu
    And I should see "Week 2" in the ucla site menu
    And I should not see "Week 3" in the ucla site menu
    When I follow "Edit settings"
    And I set the following fields to these values:
      | Number of sections | 3 |
    And I press "Save and display"
    And I reload the page
    Then I should see "Week 3" in the ucla site menu