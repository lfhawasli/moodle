@ucla @local_ucla_syllabus
Feature: Add syllabus manager link in Control Panel
  As an instructor
  I want to be able to upload a public or private syllabus to my course via Control Panel
  So that the appropiate audience can view the course syllabus

Background:
    Given I am in a ucla environment
    And the following "users" exists:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
      | student1 | Student | 1 | student1@asd.com |
    And the following ucla "sites" exists:
      | fullname | shortname | type |
      | course 1 | C1 | srs |
      | course 2 | C2 | srs |
    And the following ucla "enrollments" exists:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as ucla "teacher1"
    And I browse to site "C1"
    And I turn editing mode on
    And I press "Control Panel"
    And I follow "Manage syllabus"
    Then I should see "Syllabus manager" in the "region-main" "region"

  @javascript
  Scenario: Upload a public syllabus through the syllabus manager link in Control Panel
    And I follow "Add syllabus"
    And I upload "lib/tests/fixtures/empty.txt" file to "File" filepicker
    And I fill in "Display name" with "Public Syllabus"
    And I select "UCLA community (login required)" radio button
    And I press "Save changes"
    Then I should see "Successfully added syllabus" in the "region-main" "region"
    When I follow "Site info"
    Then I should see "Public Syllabus" in the "region-pre" "region"
    When I follow "Public Syllabus"
    Then I should see "Public Syllabus" in the "region-main" "region"

  @javascript
  Scenario: Upload a public syllabus through the syllabus manager link in Control Panel
    Given I follow "Add restricted syllabus"
    And I upload "lib/tests/fixtures/empty.txt" file to "File" filepicker
    And I fill in "Display name" with "Private Syllabus"
    And I press "Save changes"
    Then I should see "Successfully added syllabus" in the "region-main" "region"
    When I follow "Site info"
    Then I should see "Private Syllabus" in the "region-pre" "region"
    When I turn editing mode off
    And I follow "Private Syllabus"
    Then I should see "Private Syllabus (restricted)*" in the "region-main" "region"  