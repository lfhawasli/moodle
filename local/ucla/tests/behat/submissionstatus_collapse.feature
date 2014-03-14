@ucla @mod_assign @core_edit @CCLE-4299
Feature: Assignment Grading Submission Status Table Collapse
  In order to input grades more efficiently
  As an instructor
  I should be able to collapse unnecessary submission information
  
  @javascript
  Scenario: An instructor grades an assignment and is able to expand and collapse
            fields in the submission status
    Given I am in a ucla environment
    Given the following "courses" exists:
        | fullname | shortname | category | groupmode |
        | Course 1 | C1 | 0 | 1 |
      And the following "users" exists:
        | username | firstname | lastname | email |
        | teacher1 | Teacher | 1 | teacher1@asd.com |
        | student1 | Student | 1 | student1@asd.com |
      And the following "course enrolments" exists:
        | user | course | role |
        | teacher1 | C1 | editingteacher |
        | student1 | C1 | student |
      And I log in as ucla "teacher1"
      And I follow "Course 1"
      And I turn editing mode on
      And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment name |
      | Description | Submit your online text |
      | assignsubmission_onlinetext_enabled | 0 |
      | assignsubmission_file_enabled | 1 |
      | Maximum number of uploaded files | 1 |
      And I log out
      And I log in as ucla "student1"
      And I follow "Course 1"
      And I follow "Test assignment name"
      And I press "Add submission"
      And I upload "lib/tests/fixtures/empty.txt" file to "File submissions" filepicker
      And I press "Save changes"
      And I press "Submit assignment"
      And I press "Continue"
      Then I should see "Submitted for grading"
      And I should see "empty.txt"
      And I should see "Not graded"
      And I should see "Due date"
      Given I log out
      And I log in as ucla "teacher1"
      And I follow "Course 1"
      And I follow "Test assignment name"
      And I follow "View/grade all submissions"
      And I click on "Grade 1, Student" "link"
      Then I should see "Submission status" in the "submissionsummarytable" "block"
      And I should see "Grading status" in the "submissionsummarytable" "block"
      And I should see "File submissions" in the "submissionsummarytable" "block"
      And I should not see "Due date" in the "submissionsummarytable" "block"
      And I should not see "Time remaining" in the "submissionsummarytable" "block"
      And I should not see "Editing status" in the "submissionsummarytable" "block"
      And I should not see "Last modified" in the "submissionsummarytable" "block"
      And I should not see "Collapse" in the "submissionsummarytable" "block"
      When I follow "Expand"
      Then I should see "Submission status" in the "submissionsummarytable" "block"
      And I should see "Grading status" in the "submissionsummarytable" "block"
      And I should see "Due date" in the "submissionsummarytable" "block"
      And I should see "Time remaining" in the "submissionsummarytable" "block"
      And I should see "Editing status" in the "submissionsummarytable" "block"
      And I should see "Last modified" in the "submissionsummarytable" "block"
      And I should see "File submissions" in the "submissionsummarytable" "block"
      And I should not see "Expand" in the "submissionsummarytable" "block"
      When I follow "Collapse"
      Then I should see "Grading status" in the "submissionsummarytable" "block"
      And I should see "File submissions" in the "submissionsummarytable" "block"
      And I should not see "Due date" in the "submissionsummarytable" "block"
      And I should not see "Time remaining" in the "submissionsummarytable" "block"
      And I should not see "Editing status" in the "submissionsummarytable" "block"
      And I should not see "Last modified" in the "submissionsummarytable" "block"
      And I should not see "Collapse" in the "submissionsummarytable" "block"
