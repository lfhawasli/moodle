@ucla @block_ucla_course_menu
Feature: Current section in the site menu block is highlighted.
    In order to see which section I am currently on
    As a user
    I want to see the section highlighted in the site menu block

Scenario: Current section is highlighted in the site menu block
    Given I am in a ucla environment
    And a ucla "srs" site exists
    And I log in as "instructor"
    And I go to the default ucla site
    When I follow the "Show all" section in the ucla site menu
    Then I should see "Show all" highlighted in the ucla site menu
    When I follow the "Site info" section in the ucla site menu
    Then I should see "Site info" highlighted in the ucla site menu
    When I follow the "Week 1" section in the ucla site menu
    Then I should see "Week 1" highlighted in the ucla site menu

