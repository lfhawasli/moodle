@ucla @gradereport_uclaforumusage
Feature: Forum reporting
  As an instructor
  I want to count the number of times students posts in forums
  So that I can grade them on participation

  Background:
    Given I am in a ucla environment
    And the following "users" exist:
    | username | firstname | lastname | email |
    | teacher1 | Teacher | 1 | teacher1@abc.com |
    | student1 | Student | 1 | student1@abc.com |
    And the following ucla "sites" exist:
    | fullname | shortname | type | numsections |
    | course 1 | C1 | srs | 3 |
    And the following ucla "enrollments" exist:
    | user | course | role |
    | teacher1 | C1 | editinginstructor |
    | student1 | C1 | student |
    And I follow "Log in"
    And I log in as "teacher1"
    And I browse to site "C1"
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
    Given I log in as "student1"
    And I browse to site "C1"
    And I follow the "Show all" section in the ucla site menu
    And I add a new discussion to "Homework Questions" forum with:
        | Subject | Homework #1 Problem 1 |
        | Message | Question |
    And I reply "Homework #1" post from "Homework Questions" forum with:
        | Message | I cannot understand question 2. Any tips? |
    And I log out
    When I log in as "teacher1"
    And I browse to site "C1"
    And I navigate to "Grades" node in "Course administration"
    And I set the field "jump" to "Forum usage report"
    # Created forum should be in third column (after Announcements and Discussion).
    # Then I should see "1" in the ".r1 .lastcol" "css_element"
    And I should see "1" in the "#id_1_Student_usertotalposts" "css_element"
    When I set the field "Show simple view" to ""
    And I press "Go"
    # Initial Posts column.
    Then I should see "1" in the "#id_1_Student_Homework_Questions_initial_posts" "css_element"
    # Resp column.
    And I should see "1" in the "#id_1_Student_Homework_Questions_responses" "css_element"

