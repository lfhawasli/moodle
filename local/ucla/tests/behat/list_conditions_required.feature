@core @core_availability @ucla @local_ucla @core_edit @CCLE-5329 @SSC-2155
Feature: Conditional restricted section lists conditions required
  In order to see required conditions
  As a user
  I need to list conditions required

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format | enablecompletion |
      | Course 1 | C1        | ucla | 1                |
    And the following "users" exist:
      | username |
      | teacher1 |
      | student1 |
      | admin1 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | admin1   | C1     | manager        |
    And the following config values are set as admin:
      | enableavailability  | 1 |

  @javascript
  Scenario: See list of conditions required for a conditional section
    # Basic setup.

    Given I am in a ucla environment
    And I log in as "admin1"
    And I follow "Course 1"
    And I turn editing mode on
    And I follow "Week 1"
    When I add a "Quiz" to section "1" and I fill the form with:
      | Name | quiz1 |
      | Description | lorem ipsum |
    And I follow "Week 2"
    When I add a "Quiz" to section "2" and I fill the form with:
      | Name | quiz2 |
      | Description | lorem ipsum |
    And I follow "Edit section"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Grade" "button" in the "Add restriction..." "dialogue"
    And I set the field "id" to "quiz1"
    And I set the field "must be <" to "1"
    And I set the field "maxval" to "100"
    And I press "Save changes"
    Given I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I follow "Week 2"
    Then I should see "Not available unless: You get an appropriate score in quiz1" in the ".availabilityinfo" "css_element"
