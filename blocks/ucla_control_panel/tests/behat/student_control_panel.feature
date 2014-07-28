@ucla @block_ucla_control_panel @scp
Feature: Control Panel
  As a student
  I want to see the control panel button
  So that I can access its tools

  Background: I am in a UCLA environment
    Given I am in a ucla environment
    And the following "users" exist:
    | username | firstname | lastname | email |
    | student1 | Student | 1 | student1@whatever.com |

    # SRS type course
    And the following ucla "sites" exist:
    | fullname | shortname | type |
    | course 1 | C1 | srs |
    And the following ucla "enrollments" exist:
    | user | course | role |
    | student1 | C1 | student |

    # Collab type course
    And the following "courses" exist:
    | fullname | shortname | format | numsections |
    | course 2 | C2 | ucla | 10 |
    And the following "course enrolments" exist:
    | user | course | role |
    | student1 | C2 | student |  
    
    Scenario: access all the functionalities on control panel

    # SRS type course
    When I log in as "student1"
    And I browse to site "C1"
    And I press "Control Panel"
    Then I should see "MyUCLA functions"
    And I should see "Grades"
    And I should see "Classmates"
    And I should see "Textbooks"
    And I should see "Edit user profile"
    And I should see "Grades"

    # Collab type course
    And I follow "course 1"
    And I press "Control Panel"
    Then I should see "Edit user profile"
    And I should see "Change password"
    And I should see "Grades"

