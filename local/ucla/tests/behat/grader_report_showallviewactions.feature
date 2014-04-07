@ucla @core_grades @core_edit @CCLE-4289
Feature: Collapse some columns for assignment grading
  As an instructor
  I want to see all 3 possible view stats for categories
  So that I know which view I am on and what other possible states there are

@javascript
Scenario: View grader report
    Given I am in a ucla environment
    And the following "courses" exists:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exists:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | T1 | teacher1@asd.com |
      | student1 | Student | S1 | student1@asd.com |
      | student2 | Student | S2 | student2@asd.com |
      | student3 | Student | S3 | student3@asd.com |
    And the following "course enrolments" exists:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
      | student3 | C1 | student |
    And I log in as ucla "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Homework 1 |
      | Description | Test assignment description |
    And I should see "Homework 1"
    And I add a "Assignment" to section "2" and I fill the form with:
      | Assignment name | Homework 2 |
      | Description | Test assignment description |
    And I should see "Homework 2"
    And I follow "Grades"
    And I expand "Categories and items" node
    And I follow "Simple view"
    And I press "Add category"
    And I fill the moodle form with:
      | Category name | Homework |
    And I press "Save changes"
    When I click on "Select Homework 1" "checkbox"
    And I click on "Select Homework 2" "checkbox"
    And I select "Homework" from "Move selected items to"
    Then I follow "Grades"
    # Make sure 3 icons appear in "Course 1" cell.
    And "Grades only" "link" should exist in the ".category.catlevel1" "css_element"
    And "Full view" "link" should exist in the ".category.catlevel1" "css_element"
    And "Aggregates only" "link" should exist in the ".category.catlevel1" "css_element"
    # Make sure 3 icons appear in "Homework" cell.
    And "Grades only" "link" should exist in the ".category.catlevel2" "css_element"
    And "Full view" "link" should exist in the ".category.catlevel2" "css_element"
    And "Aggregates only" "link" should exist in the ".category.catlevel2" "css_element"


