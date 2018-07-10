@ucla @local_ucla_syllabus
Feature: Viewing a public or private syllabus
  As a member of the UCLA community or an enrolled student
  I want to be able to view a course syllabus
  So that I can learn more about a course

Background:
    Given I am in a ucla environment
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher  | 1 | teacher1@asd.com |
      | student1 | Student1 | 1 | student1@asd.com |
      | student2 | Student2 | 1 | student2@asd.com |
    And the following ucla "sites" exist:
      | fullname | shortname | type |
      | Course 1 | C1 | srs |
    And the following ucla "enrollments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I follow "Syllabus (empty)"

  @javascript
  Scenario: Viewing a public syllabus
    Given I follow "Add syllabus"
    And I upload "lib/tests/fixtures/empty.txt" file to "File" filemanager
    And I set the field "Display name" to "Test Syllabus"
    And I set the field "UCLA community (login required)" to "1"
    And I press "Save changes"
    Then I should see "Successfully added syllabus" in the "region-main" "region"
    And I log out
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test Syllabus"
    Then I should see "Test Syllabus" in the "region-main" "region"
    And I should see "Download: Test Syllabus" in the "region-main" "region"

  @javascript
  Scenario: Viewing a private syllabus
    Given I follow "Add restricted syllabus"
    And I upload "lib/tests/fixtures/empty.txt" file to "File" filemanager
    And I set the field "Display name" to "Test Syllabus"
    And I press "Save changes"
    Then I should see "Successfully added syllabus" in the "region-main" "region"
    And I log out
    Given I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test Syllabus"
    Then I should see "Test Syllabus" in the "region-main" "region"
    And I should see "Download: Test Syllabus" in the "region-main" "region"
