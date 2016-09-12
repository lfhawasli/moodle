@ucla @block_ucla_course_menu
Feature: "Show all" displays all the sections
  In order to see all course content
  As a user
  I want to be able to see all content on a single page

  Background:
    Given I am in a ucla environment
    And the following ucla "sites" exist:
      | fullname | shortname | type | numsections |
      | Course 1 | C1 | srs | 3 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@abc.com |
    And the following ucla "enrollments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I browse to site "C1"

  Scenario: "Show all" works
    Then I should see "Show all" in the ucla site menu
    When I follow the "Show all" section in the ucla site menu
    Then I should see "Week 1" in the "region-main" "region"
    And I should see "Week 2" in the "region-main" "region"
    And I should see "Week 3" in the "region-main" "region"