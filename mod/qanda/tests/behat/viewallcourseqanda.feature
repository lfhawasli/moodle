@ucla @mod @mod_qanda
Feature: View all course Q&A activities
  As an instructor
  I want to view all course Q&A activities
  So that I can quickly access them.

Scenario: View all course Q&A activities as an instructor.
    Given I am in a ucla environment
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | T1 | teacher1@asd.com |
    And the following ucla "sites" exist:
        | fullname | shortname | type |
        | Course 1 | C1 | srs |
    And the following ucla "enrollments" exist:
        | user | course | role |
        | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    When I browse to site "C1"
    Then I should not see "Q&As"
    And I turn editing mode on
    And I add a "Q&A" to section "0" and I fill the form with:
      | Name | Q&A test 1 |
      | Description | Description 1 |
    Then I should see "Q&As"
    And I add a "Q&A" to section "0" and I fill the form with:
      | Name | Q&A test 2 |
      | Description | Description 2 |
    When I follow "Q&As"
    Then I should see "Q&A test 1"
    And I should see "Q&A test 2"
    When I follow "Q&A test 1"
    Then I should see "Description 1"