@ucla @local_ucla_syllabus
Feature: Uploading public, preview, and private syllabi
  As an instructor
  I want to upload public, preview, and private syllabi to my course
  So that the appropiate audience can view the course syllabus

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
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I follow "Syllabus (empty)"

  @javascript
  Scenario: Uploading a UCLA public syllabus
    Given I follow "Add syllabus"
    And I upload "lib/tests/fixtures/empty.txt" file to "File" filemanager
    And I set the field "Display name" to "Test Syllabus"
    And I set the field "UCLA community (login required)" to "1"
    And I press "Save changes"
    Then I should see "Successfully added syllabus" in the "region-main" "region"
    When I follow "Site info"
    Then I should see "Test Syllabus" in the "region-pre" "region"
    When I follow "Test Syllabus"
    Then I should see "Test Syllabus" in the "region-main" "region"

  @javascript
  Scenario: Uploading a world public syllabus
    Given I follow "Add syllabus"
    And I upload "lib/tests/fixtures/empty.txt" file to "File" filemanager
    And I set the field "Display name" to "Test Syllabus"
    And I set the field "UCLA community (login required)" to "1"
    And I press "Save changes"
    Then I should see "Successfully added syllabus" in the "region-main" "region"
    When I follow "Site info"
    Then I should see "Test Syllabus" in the "region-pre" "region"
    When I turn editing mode off
    And I follow "Test Syllabus"
    Then I should see "Test Syllabus" in the "region-main" "region"

  @javascript
  Scenario: Uploading a preview syllabus
    Given I follow "Add syllabus"
    And I upload "lib/tests/fixtures/empty.txt" file to "File" filemanager
    And I set the field "Display name" to "Test Syllabus"
    And I set the field "UCLA community (login required)" to "1"
    And I set the field "This is a preview syllabus and is subject to change." to "1"
    And I press "Save changes"
    Then I should see "Successfully added syllabus" in the "region-main" "region"
    When I follow "Site info"
    Then I should see "Test Syllabus" in the "region-pre" "region"
    When I turn editing mode off
    And I follow "Test Syllabus"
    Then I should see "Test Syllabus (preview)*" in the "region-main" "region"

  @javascript
  Scenario: Uploading a private syllabus
    Given I follow "Add restricted syllabus"
    And I upload "lib/tests/fixtures/empty.txt" file to "File" filemanager
    And I set the field "Display name" to "Test Syllabus"
    And I press "Save changes"
    Then I should see "Successfully added syllabus" in the "region-main" "region"
    When I follow "Site info"
    Then I should see "Test Syllabus" in the "region-pre" "region"
    When I turn editing mode off
    And I follow "Test Syllabus"
    Then I should see "Test Syllabus" in the "region-main" "region"  
