@ucla @theme_uclashared
Feature: Course sections are showing empty when there is only a description
  In order to remove empty tag to course sections
  As a teacher
  I need to test by adding description to the section

  Background:
    Given I am in a ucla environment
    And the following ucla "sites" exist:
      | fullname | shortname | type |
      | Course 1 | C1 | srs |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
      | student1 | Student | 1 | student1@asd.com |
    And the following ucla "enrollments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "student1"
    And I browse to site "C1"
    And I should not see "Week 1" in the ucla site menu
    And I log out
    And I log in as "teacher1"
    And I browse to site "C1"
    And I turn editing mode on
    And I follow the "Week 1 (empty)" section in the ucla site menu

  Scenario: Adding description should allow the student to view the section
    When I click on "Edit section" "link"
    And I set the following fields to these values:
      | id_summary_editor | section Week 1 desc |
    And  I press "Save changes"
    Then I should see "Week 1" in the ucla site menu
    And I log out
    When I log in as "student1"
    And I browse to site "C1"
    Then I should see "Week 1" in the ucla site menu

  @javascript
  Scenario: Adding a new file should allow the student to view the section
    When I add a "File" to section "1" and I fill the form with:
      | Name | File upload test |
    And I upload "lib/tests/fixtures/empty.txt" file to "Select files" filemanager
    And I press "Save and return to course"
    And I turn editing mode off
    Then I should see "File upload test"
    Then I should see "Week 1" in the ucla site menu
    And I log out
    And I log in as "student1"
    And I browse to site "C1"
    Then I should see "Week 1" in the ucla site menu