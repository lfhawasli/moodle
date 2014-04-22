@ucla @ucla_syllabus
Feature: Viewing a private syllabus
  As a student
  I want to be able to view the course syllabus
  So that I can learn more about the course I am enrolled in

Background:
    Given I am in a ucla environment
    And the following "users" exists:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
      | student1 | Student | 1 | student1@asd.com |
    And the following ucla "sites" exists:
        | fullname | shortname | type |
        | course 1 | C1 | srs |
    And the following ucla "enrollments" exists:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as ucla "teacher1"
    And I browse to site "C1"
    And I turn editing mode on
    And I follow "Syllabus (empty)"
    And I follow "Add restricted syllabus"
    And I upload "lib/tests/fixtures/empty.txt" file to "File" filepicker
    And I fill in "Display name" with "Test Syllabus"
    And I press "Save changes"
    Then I should see "Successfully added syllabus" in the "region-main" "region"
    And I log out

  @javascript
  Scenario: Viewing a public syllabus
    Given I log in as ucla "student1"
    And I browse to site "C1"
    And I follow "Test Syllabus"
    Then I should see "Test Syllabus (restricted)*" in the "region-main" "region"
    And I should see "Download: Test Syllabus" in the "region-main" "region"