@ucla @grader_report_ucla
Feature: Grader Report Grouping filter
  In order to input grades more efficiently
  As an instructor
  I need to be able to filter students in the grader report by section

  Background: Set up UCLA environment where srs site exists with students 
              enrolled in different sections
    Given I am in a ucla environment
    And the following "users" exists: 
      | username | firstname | lastname | email |
      | instructor | editing | instructor | prof@asd.com |
      | C1user1 | test1 | user1 | C1user1@asd.com |
      | C1user2 | test2 | user2 | C1user2@asd.com |
      | C1user3 | test3 | user3 | C1user3@asd.com |
    And the following "courses" exists:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exists:
      | user | course | role |
      | instructor | C1 | editingteacher |
      | C1user1 | C1 | student |
      | C1user2 | C1 | student |
      | C1user3 | C1 | student |
    And the following "groups" exists:
      | name | course | idnumber |
      | Group 1 | C1 | G1 |
      | Group 2 | C1 | G2 |
    And the following "groupings" exists:
      | name | course | idnumber |
      | C1Section 1 | C1  | GG1 |
      | C1Section 2 | C1 | GG2 |
    And the following "grouping groups" exists:
      | grouping | group |
      | GG1 | G1 |
      | GG2 | G2 |
    And the following "group members" exists:
      |  user | group |
      | C1user1 | G1 |
      | C1user2 | G1 |
      | C1user3 | G2 |    

  @javascript
  Scenario: View different groupings in grader report
    Given I log in as ucla "instructor"
    When I go to a ucla srs site
    And I follow "Grades"
    Then I should see "Grader report"
    And I should see "View grouping"
    And the "grouping" select box should contain "All"
    And the "grouping" select box should contain "C1Section 1"
    And the "grouping" select box should contain "C1Section 2"
    When I select "C1Section 1" from "grouping"
    Then I should see "C1user1@asd.com"
    And I should see "C1user2@asd.com"
    And I should not see "C1user3@asd.com"
    When I select "C1Section 2" from "grouping"
    Then I should see "C1user3@asd.com"
    And I should not see "C1user1@asd.com"
    And I should not see "C1user2@asd.com"
    When I select "All" from "grouping"
    Then I should see "C1user1@asd.com"
    And I should see "C1user2@asd.com"
    And I should see "C1user3@asd.com"

  @javascript
  Scenario: Use ordering tools with grouping filter
    Given I log in as ucla "instructor"
    When I go to a ucla srs site
    And I follow "Grades"
    And I select "C1Section 1" from "grouping"
    Then "C1user1@asd.com" "table_row" should appear before "C1user2@asd.com" "table_row"
    When I follow "Email address"
    And I wait "1" seconds
    Then "C1user2@asd.com" "table_row" should appear before "C1user1@asd.com" "table_row"
    When I select "All" from "grouping"
    Then "C1user3@asd.com" "table_row" should appear before "C1user2@asd.com" "table_row"
    And "C1user2@asd.com" "table_row" should appear before "C1user1@asd.com" "table_row"

  @javascript
  Scenario: Use grouping filter with a grouping containing two groups to 
             simulate cross-listed courses
    Given the following "users" exists: 
      | username | firstname | lastname | email |
      | C1user4 | test4 | user4 | C1user4@asd.com |
    And the following "course enrolments" exists:
      | user | course | role |
      | C1user4 | C1 | student |
    And the following "groups" exists:
      | name | course | idnumber |
      | Group 3 | C1 | G3 |
    And the following "groupings" exists:
      | name | course | idnumber |
      | C1Section 3 | C1 | GG3 |
      | C1Section 2-3 | C1 | GG4 |
    And the following "grouping groups" exists:
      | grouping | group |
      | GG3 | G3 |
      | GG4 | G2 |
      | GG4 | G3 |
    And the following "group members" exists:
      |  user | group |
      | C1user4 | G3 |
    Given I log in as ucla "instructor"
    When I go to a ucla srs site
    And I follow "Grades"
    Then I should see "Grader report"
    And I should see "View grouping"
    And the "grouping" select box should contain "All"
    And the "grouping" select box should contain "C1Section 1"
    And the "grouping" select box should contain "C1Section 2"
    And the "grouping" select box should contain "C1Section 3"
    And the "grouping" select box should contain "C1Section 2-3"
    When I select "C1Section 2" from "grouping"
    Then I should see "C1user3@asd.com"
    And I should not see "C1user4@asd.com"
    When I select "C1Section 2-3" from "grouping"
    Then I should see "C1user3@asd.com"
    And I should see "C1user4@asd.com"
