@ucla @enrol_cohort @core_edit @CCLE-5352
Feature: Hide "Enrol cohort" button
  As a manager
  I do not want to see the "Enrol cohort" button
  So that I do not accidently enrol a cohort

Background:
    Given the following "cohorts" exist:
      | name     | idnumber |
      | Cohort 1 | COHORT1  |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname | email            |
      | manager  | Manager   | M1       | manager1@asd.com |
    And the following "role assigns" exist:
      | user    | role    | contextlevel | reference |
      | manager | manager | System       |           |

Scenario: In UCLA environment
    Given I am in a ucla environment
    And I log in as "manager"
    And I follow "Course 1"
    And I expand "Users" node
    When I follow "Enrolled users"
    Then I should not see "Enrol cohort"

# Need to run in non-js mode, so that we can find text on button.
Scenario: In regular Moodle environment
    Given I log in as "manager"
    And I follow "Course 1"
    And I expand "Users" node
    When I follow "Enrolled users"
    Then I should see "Enrol cohort"
