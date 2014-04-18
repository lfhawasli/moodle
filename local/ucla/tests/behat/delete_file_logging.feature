@ucla @core_edit @CCLE-3843
Feature: Logging file deletion
  In order to recover a file
  As a system administrator
  I need to see the file deletion logged in the activity report

  Background: Course with teacher exists.
    Given the following "users" exists:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher@asd.com |
    And the following "courses" exists:
      | fullname | shortname |
      | Course 1 | C1 |
    And the following "course enrolments" exists:
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
    And I follow "Add an activity or resource"
    And I select "File" radio button
    And I press "Add"
    And I fill the moodle form with:
      | Name | File to delete |
    And I upload "lib/tests/fixtures/empty.txt" file to "Select files" filepicker
    And I press "Save and return to course"
    And I should see "File to delete"
    And I delete "File to delete" activity
    And I expand "Reports" node
    And I follow "Logs"
    When I select "delete" from "modaction"
    And I press "Get these logs"
    Then I should see "empty.txt | 0fd8a80d18c4531c1d31cc912256d18c080c92cf"

  @javascript
  Scenario: Make sure that we can deactivate logging file deletion.
    Given I log in as "admin"
    And I set the following administration settings values:
      | Log file deletion | 0 |
    And I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I follow "Add an activity or resource"
    And I select "File" radio button
    And I press "Add"
    And I fill the moodle form with:
      | Name | File to delete |
    And I upload "lib/tests/fixtures/empty.txt" file to "Select files" filepicker
    And I press "Save and return to course"
    And I should see "File to delete"
    And I delete "File to delete" activity
    And I expand "Reports" node
    And I follow "Logs"
    When I select "delete" from "modaction"
    And I press "Get these logs"
    Then I should not see "empty.txt | 0fd8a80d18c4531c1d31cc912256d18c080c92cf"

 @javascript
  Scenario: Logging deletion of syllabus
    Given I am in a ucla environment
    And a ucla "class" site exists
    And I log in as "instructor"
    And I browse to site "class site"
    And I turn editing mode on
    And I add a new public syllabus
    And I delete a public syllabus
    And I expand "Reports" node
    And I follow "Logs"
    When I select "delete" from "modaction"
    And I press "Get these logs"
    Then I should see "empty.txt | 0fd8a80d18c4531c1d31cc912256d18c080c92cf"
