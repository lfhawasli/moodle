@ucla @local_ucla_syllabus 
Feature: Viewing a public or private syllabus
  As a member of the UCLA community or an enrolled student
  I want to be able to view a course syllabus
  So that I can learn more about a course

Background:
    Given I am in a ucla environment
    And the following "users" exists:
      | username | firstname | lastname | email |
      | teacher1 | Teacher  | 1 | teacher1@asd.com |
      | student1 | Student1 | 1 | student1@asd.com |
      | student2 | Student2 | 1 | student2@asd.com |
    And the following ucla "sites" exists:
      | fullname | shortname | type |
      | course 1 | C1 | srs |
    And the following ucla "enrollments" exists:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student2 | C1 | student |
    And I log in as ucla "teacher1"
    And I browse to site "C1"
    And I turn editing mode on
    And I follow "Syllabus (empty)"

  @javascript
  Scenario: Viewing a public syllabus
    Given I follow "Add syllabus"
    And I upload "lib/tests/fixtures/empty.txt" file to "File" filepicker
    And I fill in "Display name" with "Test Syllabus"
    And I select "UCLA community (login required)" radio button
    And I press "Save changes"
    Then I should see "Successfully added syllabus" in the "region-main" "region"
    And I log out
    Given I log in as ucla "student1"
    And I wait "120" seconds
    And I browse to site "C1"
    And I follow "Test Syllabus"
    Then I should see "Test Syllabus" in the "region-main" "region"
    And I should see "Download: Test Syllabus" in the "region-main" "region"

  @javascript
  Scenario: Viewing a private syllabus
    Given I follow "Add restricted syllabus"
    And I upload "lib/tests/fixtures/empty.txt" file to "File" filepicker
    And I fill in "Display name" with "Test Syllabus"
    And I press "Save changes"
    Then I should see "Successfully added syllabus" in the "region-main" "region"
    And I log out
    Given I log in as ucla "student2"
    And I browse to site "C1"
    And I follow "Test Syllabus"
    Then I should see "Test Syllabus (restricted)*" in the "region-main" "region"
    And I should see "Download: Test Syllabus" in the "region-main" "region"