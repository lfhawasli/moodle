@ucla @block_ucla_course_menu @javascript @CCLE-6736
Feature: Restricted sections
  As a teacher
  I do not want students to see restricted sections they do not have access to
  So that they do not see content they are not suppose to yet

  Background: UCLA environment and srs site exists
    Given I am in a ucla environment
    And the following ucla "sites" exist:
      | fullname | shortname | type |
      | Course 1 | C1        | srs  |
    And the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
      | student1 | Student   | 1        | student1@asd.com |
    And the following ucla "enrollments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And I log in as "teacher1"
    And I browse to site "C1"

  Scenario: Student restricted access to section (allow visiblity)
    Given I turn editing mode on
    And I follow the "Week 3" section in the ucla site menu
    And I open section "3" edit menu
    And I click on "a.icon.edit.menu-action" "css_element"
    And I set the following fields to these values:
      | Summary | Test |
    And I expand all fieldsets
    # Set date to far in the future.
    And I press "Add restriction..."
    And I click on "Date" "button" in the "Add restriction..." "dialogue"
    And I set the field "x[year]" to "2030"
    And I press "Save changes"
    And I log out
    When I log in as "student1"
    And I browse to site "C1"
    Then I should see "Week 3" in the ucla site menu

  Scenario: Student restricted access to section (disallow visiblity)
    Given I turn editing mode on
    And I follow the "Week 3" section in the ucla site menu
    And I open section "3" edit menu
    And I click on "a.icon.edit.menu-action" "css_element"
    And I set the following fields to these values:
      | Summary | Test |
    And I expand all fieldsets
    # Set date to far in the future.
    And I press "Add restriction..."
    And I click on "Date" "button" in the "Add restriction..." "dialogue"
    And I set the field "x[year]" to "2030"
    # Make section not visible if user does not meet restriction.
    And I click on ".availability-item .availability-eye" "css_element"
    And I press "Save changes"
    And I log out
    When I log in as "student1"
    And I browse to site "C1"
    Then I should not see "Week 3" in the ucla site menu
    