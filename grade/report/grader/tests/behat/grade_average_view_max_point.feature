@ucla @gradereport_grader @javascript @core_edit @CCLE-5032
Feature: Average grade handles suspended enrolments properly.
  In order to trust grade averages
  As a teacher
  I need to assume that suspended students are handled properly.

  Scenario: Change report preference and ensure the average grade display correctly in the gradebook
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | topics |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
      | student1 | Student | 1 | student1@asd.com |
      | student2 | Student | 2 | student2@asd.com |
      | student3 | Student | 3 | student3@asd.com |
      | student4 | Student | 4 | student4@asd.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
      | student3 | C1 | student |
      | student4 | C1 | student |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment name 1 |
      | Description | Submit your online text |
      | assignsubmission_onlinetext_enabled | 1 |
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I follow "Test assignment name 1"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | This is a submission for assignment 1 from student 1 |
    And I press "Save changes"
    And I should see "Submitted for grading"
    And I log out
    And I log in as "student2"
    And I follow "Course 1"
    And I follow "Test assignment name 1"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | This is a submission for assignment 1 from student 2 |
    And I press "Save changes"
    And I should see "Submitted for grading"
    And I log out
    And I log in as "student3"
    And I follow "Course 1"
    And I follow "Test assignment name 1"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | This is a submission for assignment 1 from student 3 |
    And I press "Save changes"
    And I should see "Submitted for grading"
    And I log out
    When I log in as "teacher1"
    And I follow "Course 1"
    And I navigate to "Grades" node in "Course administration"
    And I turn editing mode on
    # Do not assign grade to student 4 to ensure the average is calculated correctly.
    And I give the grade "100.00" to the user "Student 1" for the grade item "Test assignment name 1"
    And I give the grade "100.00" to the user "Student 2" for the grade item "Test assignment name 1"
    And I give the grade "100.00" to the user "Student 3" for the grade item "Test assignment name 1"
    And I press "Save changes"
    Then I should see "100.00" in the "Overall average" "table_row"
    And I follow "Course 1"
    And I expand "Users" node
    And I follow "Enrolled users"
    # User grade report preference set show only active enrollment to default (Yes).
    And I click on "//a[contains(@href, 'editenrolment')]" "xpath_element" in the "Student 3" "table_row"
    And I set the field "Status" to "Suspended"
    And I press "Save changes"
    And I follow "Course 1"
    And I navigate to "Grades" node in "Course administration"
    And I should see "100.00" in the "Overall average" "table_row"
    # User grade report preference set show only active enrollment to Yes.
    And I expand "Setup" node
    And I follow "Preferences: Grader report"
    And I set the field "grade_report_showonlyactiveenrol" to "Yes"
    And I press "Save changes"
    And I should see "100.00" in the "Overall average" "table_row"
    # User grade report preference set show only active enrollment to No.
    And I expand "Setup" node
    And I follow "Preferences: Grader report"
    And I set the field "grade_report_showonlyactiveenrol" to "No"
    And I press "Save changes"
    And I should see "100.00" in the "Overall average" "table_row"