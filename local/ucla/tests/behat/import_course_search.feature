@ucla @local_ucla @core_backup @core_edit @CCLE-5328 @SSC-2576
Feature: Search and filter by instructor in the Import Course Search feature.
  In order to search for courses to import effectively
  As an instructor
  I want to be able to search for courses by course instructor.

  Background:
    Given I am in a ucla environment
    And the following "users" exist:
      | username     | firstname  | lastname | email               |
      | instructor1  | Instructor | One      | instructor1@asd.com |
      | instructor2  | Instructor | Two      | instructor2@asd.com |
      | instructor3  | Instructor | Three    | instructor3@asd.com |
    And the following ucla "sites" exist:
      | fullname | shortname | type |
      | Course 1 | C1        | srs  |
      | Course 2 | C2        | srs  |
      | Course 3 | C3        | srs  |
    And the following ucla "enrollments" exist:
      | user        | course  | role                   |
      | instructor1 | C1      | editinginstructor      |
      | instructor2 | C2      | studentfacilitator     |
      | instructor3 | C3      | ta_instructor          |

  @javascript
  Scenario: Search for classes in import by course instructor.
    Given I log in as "admin"
    And I browse to site "C1"
    And I follow "Admin panel"
    And I follow "Import Moodle course data"
    And I set the field "search" to "Course"
    And I press "Search"
    Then I should see "One, Instructor"
    And I should see "Two, Instructor"
    And I should see "Three, Instructor"
    And I set the field "search" to "Course 1"
    And I press "Search"
    Then I should see "One, Instructor"
    And I should not see "Two, Instructor"
    And I should not see "Three, Instructor"
    And I set the field "search" to "Instructor"
    And I press "Search"
    Then I should see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"
    And I should see "One, Instructor"
    And I should see "Two, Instructor"
    And I should see "Three, Instructor"
    And I set the field "search" to "One, Instructor"
    And I press "Search"
    Then I should see "Course 1"
    And I should not see "Course 2"
    And I should not see "Course 3"
    And I should see "One, Instructor"
    And I should not see "Two, Instructor"
    And I should not see "Three, Instructor"
    And I set the field "search" to "Two"
    And I press "Search"
    Then I should not see "Course 1"
    And I should see "Course 2"
    And I should not see "Course 3"
    And I should not see "One, Instructor"
    And I should see "Two, Instructor"
    And I should not see "Three, Instructor"
