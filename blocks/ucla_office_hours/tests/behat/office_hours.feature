@ucla @block_ucla_office_hours
Feature: Update Office Hours Error Message for Long String
  As an instructor
  I need to see a proper error message for an invalid input string
  so that I can update my office hours

  Background: 
    Given I am in a ucla environment
    And the following "users" exists:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
    And the following ucla "sites" exists:
        | fullname | shortname | type |
        | course 1 | C1 | srs |
    And the following ucla "enrollments" exists:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as ucla "teacher1"
    And I browse to site "C1"
    And I turn editing mode on
    And I update office hours for "1, Teacher"
    Then I should see "Office hours"

#  @javascript
  Scenario: Update office hours with valid string
    When I fill in "officehours" with "Tuesday 1:00-2:00pm Wednesday 11:00-11:50am Thursday 2:00-3:00pm"
    And I press "Save changes"
    Then I should see "Successfully updated office hours and contact information."
    When I press "Continue"
    Then I should see "Tuesday 1:00-2:00pm Wednesday 11:00-11:50am Thursday 2:00-3:00pm"

#  @javascript
  Scenario: Attempt to update office hours with invalid string and re-enter valid string
    When I fill in "officehours" with "Tuesday 1:00pm-2:00pm Wednesday 11:00pm-11:50am Thursday 2:00pm-3:00pm"
    And I press "Save changes"
    Then I should see "Maximum of 64 characters."
    And I should see "Try using a shorter format (e.g. M 11:30a-12:30p)."
    When I fill in "officehours" with "Tuesday 1:00-2:00pm Wednesday 11:00-11:50am Thursday 2:00-3:00pm"
    And I press "Save changes"
    Then I should see "Successfully updated office hours and contact information."
    When I press "Continue"
    Then I should see "Tuesday 1:00-2:00pm Wednesday 11:00-11:50am Thursday 2:00-3:00pm"
     