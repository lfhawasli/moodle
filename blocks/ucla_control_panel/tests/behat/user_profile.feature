@ucla @block_ucla_control_panel
Feature: Control Panel user profile
  In order to edit my profile
  As an student
  I need to access the user profile page through the control panel

Scenario: Editing user profile as student
    Given I am in a ucla environment
    And the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student@asd.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | ucla |
    And the following "course enrolments" exist:
      | user | course | role |
      | student1 | C1 | student |
    And I log in as "student1"
    And I follow "Course 1"
    And I press "Control Panel"
    When I follow "Edit user profile"
    Then I should see "First name"
