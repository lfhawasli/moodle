@ucla @local_ucla @core_edit @CCLE-3843
Feature: Logging file deletion
  In order to recover a file
  As a system administrator
  I need to see the file deletion logged in the activity report

  Background: Course with teacher exists.
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher@asd.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "admin"
    And I set the following administration settings values:
      | Log file deletion | 1 |
    And I log out

  # File picker requires javascript.
  @javascript
  Scenario: Logging file deletion.
    Given I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "File" to section "1"
    And I set the following fields to these values:
      | Name | File to delete |
    And I upload "lib/tests/fixtures/empty.txt" file to "Select files" filemanager
    And I press "Save and return to course"
    And I should see "File to delete"
    When I open "File to delete" actions menu
    And I delete "File to delete" activity
    Then I expand "Reports" node
    And I follow "Logs"
    And I set the field "modaction" to "Delete"
    And I press "Get these logs"
    And I should see "empty.txt | 0fd8a80d18c4531c1d31cc912256d18c080c92cf"

  @javascript
  Scenario: Make sure that we can deactivate logging file deletion.
    Given I log in as "admin"
    And I set the following administration settings values:
      | Log file deletion | 0 |
    And I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "File" to section "1"
    And I set the following fields to these values:
      | Name | File to delete |
    And I upload "lib/tests/fixtures/empty.txt" file to "Select files" filemanager
    And I press "Save and return to course"
    And I should see "File to delete"
    When I open "File to delete" actions menu
    And I delete "File to delete" activity
    Then I expand "Reports" node
    And I follow "Logs"
    And I set the field "modaction" to "Delete"
    And I press "Get these logs"
    And I should not see "empty.txt | 0fd8a80d18c4531c1d31cc912256d18c080c92cf"

 @javascript
  Scenario: Logging deletion of syllabus
    Given I am in a ucla environment
    And a ucla "class" site exist
    And I log in as "instructor"
    And I follow "class site"
    When I turn editing mode on
    And I add a new public syllabus
    And I delete a public syllabus
    Then I expand "Reports" node
    And I follow "Logs"
    And I set the field "modaction" to "Delete"
    And I press "Get these logs"
    And I should see "empty.txt | 0fd8a80d18c4531c1d31cc912256d18c080c92cf"
