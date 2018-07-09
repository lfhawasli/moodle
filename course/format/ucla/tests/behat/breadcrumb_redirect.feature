@ucla  @format_ucla @CCLE-5389
Feature: Course section hyperlink in breadcrumbs
  In order to view a course section from an activity or resource
  As a user
  I need to be able to click on the course section in the breadcrumbs

  Background:
    Given I am in a ucla environment
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | ucla   |
    And the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
      | student1 | Student   | 1        | student1@asd.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    Given I log in as "teacher1"
    When I follow "Course 1"
    And I turn editing mode on
    And I follow the "Week 1" section in the ucla site menu
    And I add a "page" to section "1" and I fill the form with:
      | Name         | Test page name    |
      | Page content | Test page content |

  @javascript
  Scenario: Click on course section in breadcrumbs as teacher user
    And I follow "Test page name"
    And I follow "Week 1"
    Then I should be on section "Week 1"

  @javascript
  Scenario: Click on course section in breadcrumbs as student user
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow the "Week 1" section in the ucla site menu
    And I follow "Test page name"
    And I follow "Week 1"
    Then I should be on section "Week 1"