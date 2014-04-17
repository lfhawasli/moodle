@ucla @core_edit @CCLE-3843
Feature: Loggin deletion of files
  In order to recover a file
  As a teacher
  I need to see the file deletion logged in the activity report

  Background: Course with teacher exists
    Given I am in a ucla environment
    Given the following "users" exists:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher@asd.com |
    And the following ucla "sites" exists:
      | fullname | shortname | type |
      | course 1 | C1 | srs |
    And the following ucla "enrollments" exists:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I browse to site "C1"
    And I turn editing mode on

  # File picker requires javascript
  @javascript
  Scenario: Logging deletion of file activity
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
    And I put a breakpoint

  # File picker requires javascript
  @javascript
  Scenario: Logging deletion of syllabus
    Given I add a new public syllabus
    Given I delete a public syllabus
    And I expand "Reports" node
    And I follow "Logs"
    When I select "delete" from "modaction"
    And I press "Get these logs"
    Then I should see "empty.txt | 0fd8a80d18c4531c1d31cc912256d18c080c92cf"
