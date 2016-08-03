@ucla @block_ucla_course_menu
Feature: Display links to all resources available in the course
  In order to list all resources of a certain type for a course
  As an instructor
  When I add a resource the site menu should have a quick link to that resource type

  Background:
    Given I am in a ucla environment
    And the following ucla "sites" exist:
      | fullname | shortname | type |
      | Course 1 | C1 | srs |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
    And the following ucla "enrollments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I browse to site "C1"
    And I turn editing mode on
    And I follow "Week 1"

  @javascript
  Scenario: Make sure "Files" section exists and works
    And I add a "File" to section "1" and I fill the form with:
      | Name | Test file to section 1 |
      | Description | Test file to section 1 description |
    And I upload "lib/tests/fixtures/empty.txt" file to "Select files" filemanager
    When I press "Save and return to course"
    Then I should see "Files" in the ucla site menu
    When I follow the "Files" section in the ucla site menu
    Then I should see "Test file to section 1" in the "region-main" "region"

  Scenario: Make sure "Pages" section exists and works
    And I add a "Page" to section "1" and I fill the form with:
      | Name | Test page to section 1 |
      | Description | Description |
      | Page content | Test page to section 1 description |
    When I should see "Pages" in the "region-pre" "region"
    Then I should see "Pages" in the ucla site menu
    When I follow "Pages"
    Then I should see "Test page to section 1" in the "region-main" "region"