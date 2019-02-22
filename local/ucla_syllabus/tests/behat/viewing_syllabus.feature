@ucla @local_ucla_syllabus @javascript
Feature: Viewing a public or private syllabus
  As a member of the UCLA community or an enrolled student
  I want to be able to view a course syllabus
  So that I can learn more about a course

Background:
    Given I am in a ucla environment
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher | Teacher  | 1 | teacher@asd.com |
      | student | Student | 1 | student@asd.com |
    And the following ucla "sites" exist:
      | fullname | shortname | type |
      | Course 1 | C1 | srs |
    And the following ucla "enrollments" exist:
      | user | course | role |
      | teacher | C1 | editingteacher |
      | student | C1 | student |
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I follow "Syllabus (empty)"

  Scenario: Viewing a public syllabus
    Given I follow "Add syllabus"
    And I upload "lib/tests/fixtures/empty.txt" file to "File" filemanager
    And I set the field "Display name" to "Test Syllabus"
    And I set the field "UCLA community (login required)" to "1"
    And I press "Save changes"
    Then I should see "Successfully added syllabus" in the "region-main" "region"
    And I log out
    Given I log in as "student"
    And I am on "Course 1" course homepage
    And I follow "Test Syllabus"
    Then I should see "Test Syllabus" in the "region-main" "region"
    And I should see "Download: Test Syllabus" in the "region-main" "region"

  Scenario: Viewing a private syllabus
    Given I follow "Add restricted syllabus"
    And I upload "lib/tests/fixtures/empty.txt" file to "File" filemanager
    And I set the field "Display name" to "Test Syllabus"
    And I press "Save changes"
    Then I should see "Successfully added syllabus" in the "region-main" "region"
    And I log out
    Given I log in as "student"
    And I am on "Course 1" course homepage
    And I follow "Test Syllabus"
    Then I should see "Test Syllabus" in the "region-main" "region"
    And I should see "Download: Test Syllabus" in the "region-main" "region"

  Scenario: Viewing past syllabus
    Given I follow "Add restricted syllabus"
    And I upload "lib/tests/fixtures/empty.txt" file to "File" filemanager
    And I press "Save changes"
    # Now hide course to simulate past course.
    And I follow "Admin panel"
    And I follow "Course visibility"
    And I set the field "Visibility" to "Hidden from students"
    And I press "Save"
    And I should see "Course is now hidden from students"
    And I log out
    When I log in as "student"
    And I view syllabus for "Course 1"
    And I should see "Download: Syllabus"
