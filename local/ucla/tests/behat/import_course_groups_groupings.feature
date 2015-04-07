@core @core_backup @core_edit @CCLE-4925 @SSC-2572
Feature: Do not include groups and groupings when importing a course to another course
  In order to not include groups and groupings when importing a course to another course
  As a teacher
  I need to to import a course to another course without groups and groupings

  @javascript
  Scenario: Do not include groups and groupings when importing a course to another course
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | ucla |
      | Course 2 | C2 | ucla |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | teacher1 | C2 | editingteacher |
    And the following "groups" exist:
      | name | description | course | idnumber |
      | Group 1 | Group description | C1 | GROUP1 |
      | Group 2 | Group description | C1 | GROUP2 |
    And the following "groupings" exist:
      | name | course | idnumber |
      | Grouping 1 | C1 | GROUPING1 |
      | Grouping 2 | C1 | GROUPING2 |
    And I log in as "teacher1"
    When I import "Course 1" course into "Course 2" course using this options:
    And I expand "Users" node
    And I follow "Groups"
    Then I should not see "Group 1"
    And I should not see "Group 2"
    And I follow "Groupings"
    And I should not see "Grouping 1"
    And I should not see "Grouping 2"