@ucla @local_ucla @mod_forum @core_edit
Feature: Forum core edits
    We have made several core edits to the forum
    In order to make the interface better for our users

@CCLE-4292 @SSC-966
Scenario: Use "Add an announcement" wording
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
        | username | firstname | lastname | email            |
        | teacher1 | Teacher   | T1       | teacher1@asd.com |
    And the following "course enrolments" exist:
        | user     | course | role           |
        | teacher1 | C1     | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Announcements"
    When I press "Add an announcement"
    Then I should see "Your new announcement"
