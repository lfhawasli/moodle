@ucla @local_ucla @core_grades @core_edit @CCLE-4289
Feature: Viewing grade categories
  As an instructor
  I want to see all 3 possible view states for categories
  So that I know which view I am on and what other possible states there are

@javascript
Scenario: View grader report
    Given I am in a ucla environment
    And the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | T1 | teacher1@asd.com |
      | student1 | Student | S1 | student1@asd.com |
      | student2 | Student | S2 | student2@asd.com |
      | student3 | Student | S3 | student3@asd.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
      | student3 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Homework 1 |
      | Description | Test assignment description |
    And I should see "Homework 1"
    And I add a "Assignment" to section "2" and I fill the form with:
      | Assignment name | Homework 2 |
      | Description | Test assignment description |
    And I should see "Homework 2"
    And I navigate to "Gradebook setup" node in "Course administration"
    And I press "Add category"
    And I set the following fields to these values:
      | Category name | Homework |
    And I press "Save changes"
    When I click on "Select Homework 1" "checkbox"
    And I click on "Select Homework 2" "checkbox"
    And I set the field "Move selected items to" to "Homework"
    And I navigate to "Grades" node in "Course administration"
    # Make sure 3 icons appear in "Course 1" cell.
    And "Change to grades only" "link" should exist in the ".category.catlevel1" "css_element"
    And "Change to full view" "link" should exist in the ".category.catlevel1" "css_element"
    And "Change to aggregates only" "link" should exist in the ".category.catlevel1" "css_element"
    # Make sure 3 icons appear in "Homework" cell.
    And "Change to grades only" "link" should exist in the ".category.catlevel2" "css_element"
    And "Change to full view" "link" should exist in the ".category.catlevel2" "css_element"
    And "Change to aggregates only" "link" should exist in the ".category.catlevel2" "css_element"
