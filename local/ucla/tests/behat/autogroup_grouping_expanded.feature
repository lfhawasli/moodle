@core @core_group @core_edit @ucla @local_ucla @CCLE-4742
Feature: Grouping in auto-create groups is automatically expanded
  In order to quickly create groups
  As a teacher
  I need to be able to see important fields quickly, such as those in Grouping.

  @javascript
  Scenario: Ensure that Grouping header is expanded
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I expand "Users" node
    And I follow "Groups"
    When I press "Auto-create groups"
    Then I should see "Grouping of auto-created groups"
    And I should see "Grouping name"
