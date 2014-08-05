@ucla @ucla_syllabus
Feature: Uploading a world public syllabus
  As an instructor
  I want to upload a public syllabus to my course
  So that the general public can view the course syllabus

Background:
    Given I am in a ucla environment
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
    And the following ucla "sites" exist:
        | fullname | shortname | type |
        | Course 1 | C1 | srs |
    And the following ucla "enrollments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |

  @javascript
  Scenario: Uploading a world public syllabus
    Given I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I follow "Syllabus (empty)"
    And I follow "Add syllabus"
    And I upload "lib/tests/fixtures/empty.txt" file to "File" filepicker
    And I fill in "Display name" with "Test Syllabus"
    And I select "General public (no login required)" radio button
    And I press "Save changes"
    Then I should see "Successfully added syllabus" in the "region-main" "region"
    When I follow "Site info"
    Then I should see "Test Syllabus" in the "region-pre" "region"
    When I turn editing mode off
    And I follow "Test Syllabus"
    Then I should see "Test Syllabus" in the "region-main" "region"