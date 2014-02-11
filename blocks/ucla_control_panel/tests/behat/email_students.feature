@ucla @block_ucla_control_panel
Feature: Control Panel Con
  In order to contact students
  As an instructor
  I want to access the Email Students page through the control panel
  
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
  
  Scenario: Email students button is visible and redirects to Announcements forum
    And I log in as ucla "teacher1"
    And I browse to site "C1"
    When I press "Control Panel"
    Then I should see "Control panel"
    And I should see "Email students"
    And I should see "(via Announcements forum)"
    When I follow "Email students"
    Then I should see "General news and announcements" in the "intro" "region"
    And I should see "Your new discussion topic"

  Scenario: Email Students link is disabled while the Announcements forum is hidden
    And I log in as ucla "teacher1"
    And I browse to site "C1"
    And I turn editing mode on
    When I click on "Hide" "link" in the "Announcements" activity
    And I press "Control Panel"
    Then I should see "Email students is disabled when the Announcements forum is hidden."
    When I follow "Make Announcements forum visible"
    Then I should see "Email students"
    And I should see "(via Announcements forum)"
    When I follow "Email students"
    Then I should see "General news and announcements" in the "intro" "region"
    And I should see "Your new discussion topic"
    When I browse to site "C1"
    Then "Announcements" activity should be visible