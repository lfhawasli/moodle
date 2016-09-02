@ucla @local_ucla @core_edit @core_admin @CCLE-3773
Feature: Access Course and category management page
  In order to manage courses and/or categories
  As a user
  I need to have access to the Course and category management page if I have the capabilities

  Scenario: No-edit access to Course and category management page as a Manager Limited
    Given the following "users" exist:
      | username |
      | user1 |
    Given the following ucla "role assigns" exist:
      | user | role | contextlevel | reference |
      | user1 | manager_limited | System | |
    Given I log in as "user1"
    When I expand "Site administration" node
    Then I should see "Manage courses and categories" in the "Administration" "block"
    When I follow "Manage courses and categories"
    Then I should not see "Create new category"

  Scenario: Manager of a category gets edit access to that category's management page only
    Given the following "users" exist:
      | username |
      | user1 |
    Given the following "categories" exist:
      | name | category | idnumber |
      | Category 1 | 0 | CAT1 |
      | Category 2 | CAT1 | CAT2 |
    Given the following ucla "role assigns" exist:
      | user | role | contextlevel | reference |
      | user1 | manager_limited | System | |
      | user1 | manager | Category | CAT2 |
    Given I log in as "user1"
    And I go to the courses management page
    Then I should not see "Create new category"
    When I click on category "Category 1" in the management interface
    Then I should not see "Create new category"
    When I click on category "Category 2" in the management interface
    Then I should see "Create new category"