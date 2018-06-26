@core @core_availability @ucla @local_ucla @core_edit @CCLE-5329 @SSC-2155
Feature: Conditional restricted activity lists conditions required
  In order to see required conditions
  As a user
  I need to list conditions required

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | ucla   |
    And the following "users" exist:
      | username |
      | teacher1 |
      | student1 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following config values are set as admin:
      | enableavailability  | 1 |

  @javascript
  Scenario: See list of conditions required for a conditional activity
    Given I am in a ucla environment
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I follow "Week 1"
    And I add a "Quiz" to section "1" and I fill the form with:
      | Name        | quiz1       |
      | Description | lorem ipsum |
    And I follow "Week 2"
    And I add a "Quiz" to section "2" and I fill the form with:
      | Name        | quiz2       |
      | Description | lorem ipsum |
    And I open "quiz2" actions menu
    And I follow "Edit settings" in the open menu
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Grade" "button" in the "Add restriction..." "dialogue"
    And I set the field "id" to "quiz1"
    And I set the field "must be <" to "1"
    And I set the field "maxval" to "100"
    And I press "Save and display"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Week 2"
    And I follow "Access restrictions"
    Then I should see "Not available unless: You get an appropriate score in quiz1" in the ".availabilityinfo" "css_element"
