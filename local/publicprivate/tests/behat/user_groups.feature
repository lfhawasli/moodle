@ucla @local_publicprivate @TESTINGCCLE-1064
Feature: Filter content visibility by groups
    As an instructor
    I would like to manage my content amongst user groups
    So that I can better target my course content to students
    
    @javascript
    Scenario Outline: Add an activity or resource and verify its visibility
                      based off of user groupings.
        Given I am in a ucla environment
        And the following "courses" exist:
          | fullname | shortname |
          | Course 1 | C1        |
        And the following "users" exist:
          | username | firstname | lastname | email |
          | teacher1 | Teacher | 1 | teacher1@asd.com |
          | student1 | Student | 1 | student1@asd.com |
          | student2 | Student | 2 | student2@asd.com |
        And the following "course enrolments" exist:
          | user | course | role           |
          | teacher1 | C1 | editingteacher |
          | student1 | C1 | student        |
          | student2 | C1 | student        |
        And the following "groups" exist:
          | name    | course | idnumber |
          | Group 1 | C1     | G1       |
        And the following "groupings" exist:
          | name       | course | idnumber |
          | Grouping 1 | C1     | GG1      |
        And the following "grouping groups" exist:
          | grouping | group |
          | GG1      | G1    |
        And the following "group members" exist:
          | user     | group |
          | student1 | G1    |
        And I log in as "teacher1"
        And I follow "Course 1"
        And I turn editing mode on
        And I add a "<Activity>" to section "1" and I fill the form with:
          | <Name field>                    | Test <Activity> name |
          | <Description field>             | Test description     |
          | Grouping                        | Grouping             |
          | Available for group members only| 1                    |
        Then I should see "(Grouping 1)"
        And I log out
        And I log in as "student1"
        And I follow "Course 1"
        Then I should see "Test <Activity> name"
        And I log out
        And I log in as "student2"
        And I follow "Course 1"
        Then I should not see "Test <Activity> name"
        Examples:
          | Activity            | Name field               | Description field |
          | Chat                | Name of this chat room   | Description       |
          | Page                | Name                     | Page content      |
          | Forum               | Forum name               | Description       |
          | Quiz                | Name                     | Description       |