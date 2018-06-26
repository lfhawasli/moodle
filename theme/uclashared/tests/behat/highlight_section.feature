@ucla @theme_uclashared
Feature: Current section in the site menu block is highlighted
  In order to see which section I am currently on
  As a user
  I want to see the section highlighted in the site menu block

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

  Scenario: Current section is highlighted in the site menu block
    Given I follow the "Show all" section in the ucla site menu
    Then I should see "Show all" highlighted in the ucla site menu
    When I follow the "Week 1" section in the ucla site menu
    Then I should see "Week 1" highlighted in the ucla site menu