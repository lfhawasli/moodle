@ucla @local_ucla @CCLE-5776 @CCLE-6931
Feature: Sort Participants by preferred name
    As an instructor
    I would like the Participants sort by First name to use Preferred name when it exists instead of legal name
    So that I can identify students by the names they prefer to use

Background:
    Given I am in a ucla environment
    And the following config values are set as admin:
      | handlepreferredname | 1 | local_ucla |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | teacher  | Teacher   | T1       | teacher1@asd.com  |
      | student1 | 1th       | S9       | student1@asd.com  |
      | student2 | 2nd       | S3       | student2@asd.com  |
      | student3 | 3rd       | S1       | student3@asd.com  |
      | student4 | 4th       | S4       | student4@asd.com  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher  | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |

Scenario: Show number of displayed users.
    Given I log in as "teacher"
    And I am on "Course 1" course homepage
    When I navigate to "Participants" node in "Course administration > Users"
    Then I follow "First name"
    And "1th" "table_row" should appear before "2nd" "table_row"
    And "2nd" "table_row" should appear before "3rd" "table_row"
    And "3rd" "table_row" should appear before "4th" "table_row"
    And "4th" "table_row" should appear before "Teacher" "table_row"
    