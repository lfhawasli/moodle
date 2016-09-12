@ucla @block_ucla_control_panel
Feature: Email students via Control Panel
  In order to contact students
  As an instructor
  I want to access the Email Students page through the control panel
  
  Background:
    Given I am in a ucla environment
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
      | student1 | Student | 1 | student1@asd.com |
    And the following ucla "sites" exist:
      | fullname | shortname | type |
      | Course 1 | C1 | srs |
    And the following ucla "enrollments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
  
  Scenario: Email students button is visible and redirects to Announcements forum
    Given I log in as "teacher1"
    And I follow "Course 1"
    When I press "Control Panel"
    Then I should see "Control panel"
    And I should see "Email students"
    And I should see "(via Announcements forum)"
    When I follow "Email students"
    Then I should see "General news and announcements" in the "intro" "region"
    And I should see "Your new announcement"

  Scenario: Email Students link is disabled while the Announcements forum is hidden
    Given I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    When I click on "Hide" "link" in the "Announcements" activity
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    Then I should not see "Announcements"
    When I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I press "Control Panel"
    Then I should see "Email students is disabled when the Announcements forum is hidden."
    When I follow "Make Announcements forum visible"
    Then I should see "Email students"
    And I should see "(via Announcements forum)"
    When I follow "Email students"
    Then I should see "General news and announcements" in the "intro" "region"
    And I should see "Your new announcement"
    When I browse to site "C1"
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    Then "Announcements" activity should be visible