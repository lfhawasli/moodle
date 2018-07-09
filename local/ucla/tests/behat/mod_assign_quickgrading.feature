@ucla @local_ucla @mod_assign @core_edit @CCLE-4297
Feature: Have "quick grading" turned on by default
  As an instructor
  I want to have "Quick edit" turned on
  so that I can quickly grade assignment submissions.

@javascript
Scenario: Check "quick grading" is enabled
    Given I am in a ucla environment
    And the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | T1 | teacher1@asd.com |
      | student1 | Student | S1 | student1@asd.com |
      | student2 | Student | S2 | student2@asd.com |
      | student3 | Student | S3 | student3@asd.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
      | student3 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment name |
      | Description | Test assignment description |
    And I follow "Test assignment name"
    When I follow "View all submissions"
    Then the field "Quick grading" matches value "1"
    When I set the field "Quick grading" to ""
    #The default table settings are updated automatically/responsively
    And I reload the page
    Then the field "Quick grading" matches value ""
