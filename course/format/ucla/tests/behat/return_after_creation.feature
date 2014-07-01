@ucla @course_format_ucla @return_test
Feature: Return after creation
    As an instructor
    I want to go to a course
    And then to a week
    turn on editing
    and add an activity
    and then see the same week

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

@javascript
 Scenario: Adding a resource
    And I log in as ucla "teacher1"
    And I browse to site "C1"
    # And I put a breakpoint
    And I reload the page
    When I follow the "Week 1" section in the ucla site menu
    Given I turn editing mode on
    When I follow "Add an activity or resource"
    And I select "Page" radio button
    And I press "Add"
    And I fill the moodle form with:
    | Name | newpage |
    | Page content | lorem ipsum |
    And I press "Save and return to course"
    Then I should see "Week 1"
    And I should see "newpage"