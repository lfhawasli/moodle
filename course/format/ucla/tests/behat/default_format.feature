@ucla @format_ucla
Feature: Default format
    In order to ensure that courses have a uniform look and feel
    As a system admin
    I want to confirm that all built courses use the "UCLA format" by default

Background:
    Given I am in a ucla environment
    And the following "users" exist:
       | username | firstname | lastname | email |
       | teacher1 | Teacher | 1 | teacher1@asd.com |
    And the following ucla "sites" exists:
       | fullname | shortname | type |
       | Test course 1 | C1 | srs |
    And the following ucla "enrollments" exists:
       | user | course | role |
       | teacher1 | C1 | editingteacher |

Scenario: Make sure ucla format is default and cannot be changed.
    Given I log in as ucla "teacher1"
    And I follow "Test course 1"
    When I follow "Edit settings"
    And I expand all fieldsets
    Then I should see "UCLA format"
    And "Format" "select" should not exist in the "Course format" "fieldset"
