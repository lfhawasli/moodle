@ucla @local_ucla_syllabus
Feature: Add syllabus manager link in Admin panel
  As an instructor
  I want to be able to upload a public or private syllabus to my course via Admin panel
  So that the appropiate audience can view the course syllabus

Background:
    Given I am in a ucla environment
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
      | student1 | Student | 1 | student1@asd.com |
    And the following ucla "sites" exist:
      | fullname | shortname | type |
      | Course 1 | C1 | srs |
    And the following ucla "enrollments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I follow "Admin panel"
    And I follow "Manage syllabus"
    Then I should see "Syllabus manager" in the "region-main" "region"

  @javascript
  Scenario: Upload a public syllabus through the syllabus manager link in Admin panel
    And I follow "Add syllabus"
    And I upload "lib/tests/fixtures/empty.txt" file to "File" filemanager
    And I set the field "Display name" to "Public Syllabus"
    And I set the field "UCLA community (login required)" to "1"
    And I press "Save changes"
    Then I should see "Successfully added syllabus" in the "region-main" "region"
    When I follow "Site info"
    Then I should see "Public Syllabus" in the "region-pre" "region"
    When I follow "Public Syllabus"
    Then I should see "Public Syllabus" in the "region-main" "region"

  @javascript
  Scenario: Upload a private syllabus through the syllabus manager link in Admin panel
    Given I follow "Add restricted syllabus"
    And I upload "lib/tests/fixtures/empty.txt" file to "File" filemanager
    And I set the field "Display name" to "Private Syllabus"
    And I press "Save changes"
    Then I should see "Successfully added syllabus" in the "region-main" "region"
    When I follow "Site info"
    Then I should see "Private Syllabus" in the "region-pre" "region"
    When I turn editing mode off
    And I follow "Private Syllabus"
    Then I should see "Private Syllabus" in the "region-main" "region"  
