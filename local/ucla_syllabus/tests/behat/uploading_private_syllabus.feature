@ucla @ucla_syllabus
Feature: Uploading a private syllabus
  As an instructor
  I want to upload a private syllabus to my course
  So that only students enrolled in the course can view the course syllabus

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
  Scenario: Uploading a private syllabus
    Given I log in as ucla "teacher1"
    And I browse to site "C1"
    And I turn editing mode on
    And I follow "Syllabus (empty)"
    And I follow "Add restricted syllabus"
    And I upload "lib/tests/fixtures/empty.txt" file to "File" filepicker
    And I fill in "Display name" with "Test Syllabus"
    And I press "Save changes"
    Then I should see "Successfully added syllabus" in the "region-main" "region"
    When I follow "Site info"
    Then I should see "Test Syllabus" in the "region-pre" "region"
    When I turn editing mode off
    And I follow "Test Syllabus"
    Then I should see "Test Syllabus (restricted)*" in the "region-main" "region"    