@mod @mod_assign @ucla @local_ucla @core_edit @CCLE-4770
Feature: Restrict grader drop down list to course members with grading permissions
  In order to be able to properly assign graders
  As an instructor
  I must only be able to see graders specific to my courses.

  @javascript
  Scenario: Check drop down list for course graders.
    Given the following ucla "sites" exist:
      | fullname | shortname | term | type |
      | course 1 | C1        | 12W  | srs  |
    And the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
      | grader   | Grader    | 1        | grader@asd.com   |
      | manager  | Manager   | 1        | manager@asd.com  |
      | student  | Student   | 1        | student@asd.com  |
    And the following "role assigns" exist:
      | user    | role           | contextlevel | reference |
      | manager | manager        | System       |           |
    And the following ucla "enrollments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | grader   | C1     | grader         |
      | student  | C1     | student        |
    When I log in as "teacher1"
    And I follow "course 1"
    And I turn editing mode on
    And I add a "Assignment" to section "0" and I fill the form with:
      | Assignment name   | Test assignment name |
      | Description       | Test description     |
      | markingworkflow   | 1                    |
      | markingallocation | 1                    |
    # Log in as a student and submit an assignment.
    And I log out
    And I log in as "student"
    And I follow "course 1"
    And I follow "Test assignment name"
    And I press "Add submission"
    And I upload "lib/tests/fixtures/empty.txt" file to "File submissions" filemanager
    And I press "Save changes"
    # Log in as a teacher and make sure that the grader list is restricted.
    And I log out
    And I log in as "teacher1"
    And I follow "course 1"
    And I follow "Test assignment name"
    And I follow "View all submissions"
    # Make sure filter dropdown only shows local graders.
    Then "Grader" "text" should exist in the "markerfilter" "select"
    And "Teacher" "text" should exist in the "markerfilter" "select"
    And "Manager" "text" should not exist in the "markerfilter" "select"
    # Make sure that grader dropdown only lists local graders.
    When I click on "Quick grading" "checkbox"
    Then "Grader" "text" should exist in the "#mod_assign_grading_r0_c5 select" "css_element"
    And "Teacher" "text" should exist in the "#mod_assign_grading_r0_c5 select" "css_element"
    And "Manager" "text" should not exist in the "#mod_assign_grading_r0_c5 select" "css_element"
    # Make sure that the edit grade screen only lists local graders.
    And I click on "Grade" "link" in the "Student 1" "table_row"
    Then "Grader" "text" should exist in the "allocatedmarker" "select"
    And "Teacher" "text" should exist in the "allocatedmarker" "select"
    And "Manager" "text" should not exist in the "allocatedmarker" "select"
    # Make sure bulk operations filter only shows local graders.
    When I press the "back" button in the browser
    And I click on "Select all" "checkbox"
    And I click on "Set allocated marker" "option" in the "select#id_operation" "css_element"
    And I click on "Go" "button" confirming the dialogue
    Then "Grader" "text" should exist in the "allocatedmarker" "select"
    And "Teacher" "text" should exist in the "allocatedmarker" "select"
    And "Manager" "text" should not exist in the "allocatedmarker" "select"
