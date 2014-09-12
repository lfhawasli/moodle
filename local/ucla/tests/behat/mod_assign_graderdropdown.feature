@mod @mod_assign @ucla @core_edit @CCLE-4770
Feature: Restrict grader drop down list to course members with grading permissions
  In order to be able to properly assign graders
  As an instructor
  I must only be able to see graders specific to my courses.

  Scenario: Check drop down list for course graders.
    And the following ucla "sites" exist:
      | fullname | shortname | term | type |
      | course 1 | C1        | 12W  | srs  |
    And the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
      | grader   | Grader    | 1        | grader@asd.com   |
      | manager  | Manager   | 1        | manager@asd.com  |
    And the following "role assigns" exist:
      | user    | role           | contextlevel | reference |
      | manager | manager        | System       |           |
    And the following ucla "enrollments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | grader   | C1     | grader         |
    And I log in as "teacher1"
    And I follow "course 1"
    And I turn editing mode on
    And I add a "Assignment" to section "0" and I fill the form with:
      | Assignment name   | Test assignment name |
      | Description       | Test description     |
      | markingworkflow   | 1                    |
      | markingallocation | 1                    |
    And I follow "Test assignment name"
    And I follow "View/grade all submissions"
    And "Grader" "text" should exist in the "markerfilter" "select"
    And "Teacher" "text" should exist in the "markerfilter" "select"
    And "Manager" "text" should not exist in the "markerfilter" "select"