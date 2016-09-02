@ucla @local_ucla @core_edit @CCLE-3797
Feature: Allow course sections to be hidden upon course import (CCLE-3797)
  In order to move and copy contents between courses
  As a teacher
  I need to be able to hide course sections when I import

  @javascript
  Scenario: Import course's contents to another course and select hide sections
    Given I am in a ucla environment
    And the following ucla "sites" exist:
      | fullname | shortname | type |
      | Course 1 | C1        | srs  |
      | Course 2 | C2        | srs  |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
    And the following ucla "enrollments" exist:
      | user | course | role |
      | teacher1 | C1 | editinginstructor |
      | teacher1 | C2 | editinginstructor |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I follow the "Show all" section in the ucla site menu
    And I add a "Database" to section "1" and I fill the form with:
      | Name | Test database name |
      | Description | Test database description |
    And I add a "Forum" to section "2" and I fill the form with:
      | Forum name | Test forum name |
      | Description | Test forum description |
    And I add a "Page" to section "3" and I fill the form with:
      | Name | Test page name |
      | Page content | Test page content |
    And I add a "Label" to section "4" and I fill the form with:
      | Label text | Test label text |
    When I import "Course 1" course into "Course 2" course using this options:
      | Schema | Hide all course sections after import/restore | Yes |
    Then the "Week 1" section in the ucla site menu is hidden
    And the "Week 2" section in the ucla site menu is hidden
    And the "Week 3" section in the ucla site menu is hidden
    And the "Week 4" section in the ucla site menu is hidden
