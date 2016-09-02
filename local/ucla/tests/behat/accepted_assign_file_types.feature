@ucla @local_ucla @mod_assign @core_edit @CCLE-4413
Feature: Restricting assignment submissions to certain file types.
    In order to make certain only certain file types are submitted
    As a teacher
    I need to to specify what file types I expect from students.

# NOTE: Need to have debugging turned off, or else Behat will complain/fail.
Background:
    # Need to be in UCLA theme for alert to show up.
    Given I am in a ucla environment
    And the following "users" exist:
        | username | firstname | lastname | email           |
        | teacher  | Teacher   | 1        | teacher@asd.com |
        | student  | Student   | 1        | student@asd.com |
    And the following "courses" exist:
        | fullname | shortname |
        | Course 1 | course1   |
    And the following "course enrolments" exist:
        | user    | course  | role           |
        | teacher | course1 | editingteacher |
        | student | course1 | student        |

@javascript
Scenario: Accept only txt files.
    Given I log in as "teacher"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Assignment" to section "1" 
    And I set the following fields to these values:
      | Assignment name | Test assignment name |
      | Description | Accept txt only |
      | assignsubmission_file_enabled | 1 |
    # Need to do setting of file type in stages, because of JS show/hide.
    And I click on "assignsubmission_usqfiletypes_enabled" "checkbox"
    And I click on "id_assignsubmission_usqfiletypes_filetypes_odttxt" "checkbox"
    And I press "Save and return to course"
    And I log out
    And I log in as "student"
    And I follow "Course 1"
    And I follow "Test assignment name"
    # First try to upload an invalid file.
    When I press "Add submission"
    # I should see the file type that is accepted.
    And I should see "Other documents (odt, txt)"
    And I upload "lib/tests/fixtures/tabfile.csv" file to "File submissions" filemanager and it fails
    Then I should see "text/csv filetype cannot be accepted."
    And I reload the page
    # Upload valid file.
    When I upload "lib/tests/fixtures/empty.txt" file to "File submissions" filemanager
    And I press "Save changes"
    Then I should see "Submitted for grading"