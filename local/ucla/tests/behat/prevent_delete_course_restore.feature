@ucla @local_ucla @CCLE-6927
Feature: Prevent instructors from using "Delete the contents of the existing course and then restore"
    As an instructor
    I should not be able to delete the contents of an existing course and then restore into it
    So I should not see the option to delete and restore under existing course

Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | ucla |
      | Course 2 | C2 | ucla |
    And the following "course enrolments" exist:
      | user        | course    | role              |
      | teacher1    | C1        | editingteacher    |
  
  @javascript 
  Scenario: Confirm that the option to delete and restore under existing course is visible for admins
    Given I am in a ucla environment
    And I log in as "admin"
    And I follow "Courses"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    When I follow the "Show all" section in the ucla site menu
    And I add a "File" to section "1"
    And I set the following fields to these values:
        | Name | Example Content |
        | Description | Content test |
    And I upload "lib/tests/fixtures/empty.txt" file to "Select files" filemanager
    And I press "Save and return to course"
    Then I should see "Example Content"
    And I backup "Course 2" course using this options:
         | Confirmation | Filename | course2.mbz |
    When I click on "Restore" "link" in the "course2.mbz" "table_row"
    And I press "Continue"
    Then I should see "Delete the contents of the existing course and then restore"
    # Radio buttons in backup/restore are not properly labeled, so have to
    # Refer to "Delete the contents of the existing course and then restore"
    # with this complicated method.
    When I click on "//div[contains(concat(' ', normalize-space(@class), ' '), 'bcs-existing-course')]/descendant::input[@type='radio'][@name='target'][@value='3']" "xpath_element"
    And I click on "targetid" "radio" in the "Course 1" "table_row"
    Then I should see "Course deletion warning"

  @javascript
  Scenario: Confirm that the option to delete and restore under existing course is not visible for instructors
    Given I log in as "teacher1"
    And I backup "Course 1" course using this options:
         | Confirmation | Filename | course1.mbz |
    When I click on "Restore" "link" in the "course1.mbz" "table_row"
    And I press "Continue"
    Then I should not see "Delete the contents of the existing course and then restore"