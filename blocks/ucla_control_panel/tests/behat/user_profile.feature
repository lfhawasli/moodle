@ucla @block_ucla_control_panel
Feature: Control Panel user profile
  In order to edit my profile
  As an student
  I need to access the user profile page through the control panel

Scenario: Editing user profile as student
    Given I am in a ucla environment
    And the following "users" exists:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student@asd.com |
    And the following "courses" exists:
      | fullname | shortname | format |
      | Course 1 | C1 | ucla |
    And the following "course enrolments" exists:
      | user | course | role |
      | student1 | C1 | student |
    And I log in as ucla "student1"
    And I follow "Course 1"
    And I press "Control Panel"
    When I follow "Edit user profile"
    Then I should see "First name"