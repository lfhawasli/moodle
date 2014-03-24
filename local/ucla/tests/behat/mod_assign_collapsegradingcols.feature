@ucla @mod_assign @core_edit @CCLE-4292
Feature: Collapse some columns for assignment grading
  As an instructor
  I want some columns collapsed by default
  so that I can better view all submissions for an assignment

# Note, need to run test without javascript, because looking for "User picture"
# will return HTML element inside a hidden accesshide span. Thus Behat will say
# that the element is also hidden, since it looks at the last matching page
# element.
Scenario: View assignment submissions table with collapsed columns
    Given I am in a ucla environment
    And the following "courses" exists:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exists:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | T1 | teacher1@asd.com |
      | student1 | Student | S1 | student1@asd.com |
      | student2 | Student | S2 | student2@asd.com |
      | student3 | Student | S3 | student3@asd.com |
    And the following "course enrolments" exists:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
      | student3 | C1 | student |
    And I log in as ucla "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment name |
      | Description | Test assignment description |
    And I follow "Test assignment name"
    When I follow "View/grade all submissions"
    Then I should not see "User picture"
    And I should not see "ID number"
    And I should not see "Email address"
    And I follow "Show User picture"
    And I should see "User picture"
    And I should not see "ID number"
    And I should not see "Email address"
    And I follow "Show ID number"
    And I should see "User picture"
    And I should see "ID number"
    And I should not see "Email address"
    And I follow "Show Email address"
    And I should see "User picture"
    And I should see "ID number"
    And I should see "Email address"
    And I follow "Hide User picture"
    And I should not see "User picture"
    And I should see "ID number"
    And I should see "Email address"
    And I follow "Hide ID number"
    And I should not see "User picture"
    And I should not see "ID number"
    And I should see "Email address"
    And I follow "Hide Email address"
    And I should not see "User picture"
    And I should not see "ID number"
    And I should not see "Email address"
