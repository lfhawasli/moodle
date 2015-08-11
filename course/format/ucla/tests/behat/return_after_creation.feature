@ucla @format_ucla @CCLE-3520
Feature: Return after creation
   In order to have consistent navigation
   As an instructor
   I want to return to the section I was previously on after adding an activity

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
    And I log in as "teacher1"
    And I follow "Test course 1"
    And I turn editing mode on
    And I follow "Modify sections"
    And I click on "landing-page-5" "radio"
    And I press "Save changes"

@javascript
Scenario Outline: Adding/editing a resource
    When I follow the "<section>" section in the ucla site menu
    And I add a "page" to section "<sectionnumber>" and I fill the form with:
       | Name | <pagename> |
       | Page content | lorem ipsum |
    Then I should be on section "<section>"
    And I should see "<pagename>"
    When I open "<pagename>" actions menu
    And I click on "Edit settings" "link" in the "#section-<sectionnumber> .page .commands" "css_element"
    And I press "Save and return to course"
    Then I should be on section "<section>"
    And I should see "<pagename>"
    Examples:
       | section   | sectionnumber | pagename |
       | Site info | 0             | newpage0 |
       | Week 1    | 1             | newpage1 |
       | Show all  | 2             | newpage2 |

@javascript
Scenario Outline: Cancel adding a resource
    When I follow the "<section>" section in the ucla site menu
    And I add a "page" to section "<sectionnumber>"
    And I press "Cancel"
    Then I should be on section "<section>"
    Examples:
       | section   | sectionnumber |
       | Site info | 0             |
       | Week 1    | 1             |
       | Show all  | 2             |
