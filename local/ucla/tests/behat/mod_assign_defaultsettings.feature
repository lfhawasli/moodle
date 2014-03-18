@ucla @mod_assign @core_edit @CCLE-3511
Feature: Have "quick grading" turned on by default
  As an instructor
  I want to see the "Options" for filtering submissions at the top
  So that I can quickly filter assignment submissions.

Scenario: Check "Options" is at the top of the page
    Given I am in a ucla environment
    And the following "courses" exists:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exists:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | T1 | teacher1@asd.com |
    And the following "course enrolments" exists:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as ucla "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    When I add a "Assignment" to section "1"
    And I expand all fieldsets
    Then the "Require students click submit button" select box should contain "Yes"
    And the "Notify graders about submissions" select box should contain "No"