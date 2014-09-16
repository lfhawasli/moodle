@ucla @gradeexport_myucla @core_edit @CCLE-4458
Feature: We can enter in grades and view reports from the gradebook
  In order to check the expected results are displayed
  As a teacher
  I need to assign grades and check that they display correctly in the gradebook.
  I need to enable grade weightings and check that they are displayed correctly.


  Background: Setup course and users
    Given I am in a ucla environment
    And the following "users" exist:
      | username | firstname | lastname | email | idnumber |
      | instructor | 1 | Instructor | instructor@ucla.edu | 123456789 |
      | student | 1 | Student | student@ucla.edu | 987654321 |
    And the following ucla "sites" exist:
        | fullname | shortname | type |
        | course 1 | C1 | srs |
    And the following ucla "enrollments" exist:
      | user | course | role |
      | instructor | C1 | editinginstructor |
      | student | C1 | student |

  @javascript
  Scenario: Grade is correctly rounded up to the next letter grade
    Given I log in as "instructor"
    And I browse to site "C1"
    And I follow "Grades"
    And I press "Administration"
    And I expand "Categories and items" node
    And I should see "Simple view"
    And I follow "Simple view"
    And I press "Add grade item"
    And I set the following fields to these values:
        | Item name | Exam |
    And I press "Save changes"
    And I follow "Grades"
    And I turn editing mode on
    When I give the grade "89.995" to the user "Student, 1" for the grade item "Exam"
    And I press "Update"
    And I press "Administration"
    And I expand "Export" node
    And I follow "MyUCLA Gradebook Express format"
    And I press "Submit"
    Then I should see "A-" in the "987654321" "table_row"
    And I follow "Grades"
    When I give the grade "89.994" to the user "Student, 1" for the grade item "Exam"
    And I press "Update"
    And I press "Administration"
    And I expand "Export" node
    And I follow "MyUCLA Gradebook Express format"
    And I press "Submit"
    Then I should see "B+" in the "987654321" "table_row"

  @javascript
  Scenario: Grade is only rounded up starting at the third decimal place
    Given I log in as "instructor"
    And I browse to site "C1"
    And I follow "Grades"
    And I press "Administration"
    And I expand "Categories and items" node
    And I should see "Simple view"
    And I follow "Simple view"
    And I press "Add grade item"
    And I set the following fields to these values:
        | Item name | Exam |
    And I press "Save changes"
    And I follow "Grades"
    And I turn editing mode on
    When I give the grade "89.99" to the user "Student, 1" for the grade item "Exam"
    And I press "Update"
    And I press "Administration"
    And I expand "Export" node
    And I follow "MyUCLA Gradebook Express format"
    And I press "Submit"
    Then I should see "B+" in the "987654321" "table_row"

  @javascript
  Scenario: Grade is rounded up when aggregate is used
    Given I log in as "instructor"
    And I browse to site "C1"
    And I follow "Grades"
    And I press "Administration"
    And I expand "Categories and items" node
    And I should see "Simple view"
    And I follow "Simple view"
    And I press "Add grade item"
    And I set the following fields to these values:
        | Item name | Exam1 |
    And I press "Save changes"
    And I press "Add grade item"
    And I set the following fields to these values:
        | Item name | Exam2 |
    And I press "Save changes"
    And I follow "Grades"
    And I turn editing mode on
    When I give the grade "80.00" to the user "Student, 1" for the grade item "Exam1"
    And I give the grade "79.99" to the user "Student, 1" for the grade item "Exam2"
    And I press "Update"
    And I press "Administration"
    And I expand "Export" node
    And I follow "MyUCLA Gradebook Express format"
    And I press "Submit"
    Then I should see "B-" in the "987654321" "table_row"