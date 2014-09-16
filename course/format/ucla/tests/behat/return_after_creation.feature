@ucla @format_ucla
Feature: Return after creation
   In order to have consistent navigation
   As an instructor
   I want to return to the section I was previously on after adding an activity

@javascript
Scenario: Adding a resource
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
    And I log in as "teacher1"
    And I follow "Test course 1"
    And I turn editing mode on
    When I follow the "Week 1" section in the ucla site menu
    And I follow "Add an activity or resource"
    And I set the field "Page" to "1"
    And I press "Add"
    And I set the following fields to these values:
       | Name | newpage |
       | Page content | lorem ipsum |
    And I press "Save and return to course"
    Then I should see "Week 1" highlighted in the ucla site menu
    And I should see "newpage"
