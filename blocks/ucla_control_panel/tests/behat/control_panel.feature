@ucla @block_ucla_control_panel
Feature: Control Panel
  As an instructor
  I want to see the control panel button
  So that I can access its tools

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

#  @javascript
  Scenario: Control Panel visiblity    
    And I log in as ucla "teacher1"
    And I browse to site "C1"
    When I press "Control Panel"
    Then I should see "Control panel"
    And I should see "Most commonly used"
