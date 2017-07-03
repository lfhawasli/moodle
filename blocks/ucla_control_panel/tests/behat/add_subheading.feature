@ucla @block_ucla_control_panel
Feature: Add subheading
    In order to easily add a label
    As an instructor
    I want to be able to use the Control Panel
  
Background:
    Given I am in a ucla environment
    And the following "users" exist:
       | username | firstname | lastname | email |
       | teacher1 | Teacher | 1 | teacher1@asd.com |
    And the following ucla "sites" exist:
       | fullname | shortname | type |
       | Test course 1 | C1 | srs |
    And the following ucla "enrollments" exist:
       | user | course | role |
       | teacher1 | C1 | editingteacher |

@javascript
Scenario: Add subheading    
    And I log in as "teacher1"
    And I follow "Test course 1"
    And I press "Control Panel"
    And I follow "Add a subheading"
    And I set the following fields to these values:   
       | Subheading you want displayed in section | subheading |
    And I press "Save changes"
    Then I should see "Successfully added subheading to section."

Scenario: Make sure subheading require text
    Given I log in as "teacher1"
    And I follow "Test course 1"
    And I press "Control Panel"
    And I follow "Add a subheading"
    And I press "Save changes"
    Then I should not see "Successfully added subheading to section."
    And I should see "You must supply a value here"
