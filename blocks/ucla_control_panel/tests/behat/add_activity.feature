@ucla @block_ucla_control_panel
Feature: Adding an activity via the Control Panel
  As an instructor
  I want to add an activity via the Control Panel
  So that I can add an activity easily

  Background:
    Given I am in a ucla environment
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
    And the following ucla "sites" exist:
        | fullname | shortname | type |
        | course 1 | C1 | srs |
    And the following ucla "enrollments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |

Scenario Outline: Adding activities
    Given I log in as "teacher1"
    And I browse to site "C1"
    When I press "Control Panel"
    And I follow "Add an activity"
    And I set the field "Activity" to "<Activity>"
    And I press "Save changes"
    Then I should see "Adding a new <Activity>"

  Examples:
    | Activity |
    | Chat     |
    | Choice   |
    | Database |
    | Forum    |
    | Glossary |
    | Lesson   |
    | Quiz     |
    | SCORM    |
    | Workshop |

