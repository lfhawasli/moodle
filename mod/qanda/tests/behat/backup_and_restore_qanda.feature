@ucla @mod @mod_qanda @CCLE-4677
Feature: Backup and restore Q&A
  In order to utilize the Q&A module
  As an instructor
  I want to backup and restore Q&A modules

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
      | Course 2 | C2 | 0 | 1 |
      | Course 3 | C3 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | T1 | teacher1@asd.com |
      | student1 | Student | S1 | student1@asd.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | teacher1 | C2 | editingteacher |
      | teacher1 | C3 | editingteacher |
      | student1 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Q&A" to section "1" and I fill the form with:
      | Name | Q&A test |
      | Description | Description |

  @javascript
  Scenario: Backup a Q&A and restore from different sources
    #Restore from 'Activity backup' area
    And I follow "Q&A test"
    And I follow "Backup"
    And I press "Next"
    And I press "Next"
    And I press "Perform backup"
    Then I should see "The backup file was successfully created."
    When I press "Continue"
    And I click on "Restore" "link" in the "#region-main" "css_element"
    And I press "Continue"
    And I click on "targetid" "radio" in the ".rcs-course.lastrow" "css_element"
    And I press "Continue"
    And I press "Next"
    And I press "Next"
    And I press "Perform restore"
    Then I should see "The course was restored successfully, clicking the continue button below will take you to view the course you restored."
    When I press "Continue"
    Then I should see "Q&A test"
    When I follow "Q&A test"
    Then I should see "Description"
    # Restore from a local file
    When I am on homepage
    And I am on "Course 1" course homepage
    And I follow "Q&A test"
    And I follow "Restore"
    And I upload "mod/qanda/tests/fixtures/qanda_backup.mbz" file to "Files" filemanager
    And I press "Restore"
    And I press "Continue"
    And I click on "targetid" "radio" in the ".rcs-course.lastrow" "css_element"
    And I press "Continue"
    And I press "Next"
    And I press "Next"
    And I press "Perform restore"
    Then I should see "The course was restored successfully"
    When I press "Continue"
    Then I should see "Q&A test"
    When I follow "Q&A test"
    Then I should see "Description"