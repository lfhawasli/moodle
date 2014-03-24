@ucla @gradereport_uclaforumusage
Feature: Forum reporting
  As an instructor
  I want to count the number of times students posts in forums
  So that I can grade them on participation

  Background:
    Given I am in a ucla environment
    And a ucla "class" site exists
    And I log in as "instructor"
    And I go to the default ucla site
    And I turn editing mode on
    And I follow the "Show all" section in the ucla site menu
    And I add a "Forum" to section "1" and I fill the form with:
        | Forum name | Homework Questions |
        | Description | Post any homework related questions. |
    And I follow the "Show all" section in the ucla site menu
    And I add a new discussion to "Homework Questions" forum with:
        | Subject | Homework #1 |
        | Message | Post |
    And I log out

  @javascript
  Scenario: Reply as student and verify counts as instructor
    Given I log in as "student"
    And I go to the default ucla site
    And I follow the "Show all" section in the ucla site menu
    And I add a new discussion to "Homework Questions" forum with:
        | Subject | Homework #1 Problem 1 |
        | Message | Question |
    And I reply "Homework #1" post from "Homework Questions" forum with:
        | Message | I cannot understand question 2. Any tips? |
    And I log out
    When I log in as "instructor"
    And I go to the default ucla site
    And I follow "Grades"
    And I follow "Forum usage report"
    # Created forum should be in third column (after Announcements and Discussion).
    Then I should see "1" in the ".r1 .lastcol" "css_element"
    When I uncheck "Show simple view"
    And I press "Go"
    # Initial Posts column.
    Then I should see "1" in the ".r0 td.c7" "css_element"
    # Resp column.
    And I should see "1" in the ".r0 td.c8" "css_element"

