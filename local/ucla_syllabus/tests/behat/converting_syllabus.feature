@ucla @local_ucla_syllabus 
Feature: Convert syllabus from public to private and vice versa
  As an instructor
  I want to be able to restrist and unrestrict a course syllabus
  So that I can control who can view the syllabus

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
  Scenario: Convert syllabus from public to private and vice versa
    Given I log in as ucla "teacher1"
    And I browse to site "C1"
    And I turn editing mode on
    And I follow "Syllabus (empty)"
    And I follow "Add syllabus"
    And I upload "lib/tests/fixtures/empty.txt" file to "File" filepicker
    And I fill in "Display name" with "Test Syllabus"
    And I select "UCLA community (login required)" radio button
    And I press "Save changes"
    Then I should see "Successfully added syllabus" in the "region-main" "region"
    When I follow "Site info"
    Then I should see "Test Syllabus" in the "region-pre" "region"
    When I follow "Test Syllabus"
    Then I should see "Syllabus manager" in the "region-main" "region"
    # Convert syllabus from public to private
    When I follow "Restrict"
    Then I should see "Successfully restricted syllabus" in the "region-main" "region"
    # Convert syllabus from private to public
    When I follow "Unrestrict"
    Then I should see "Successfully unrestricted syllabus" in the "region-main" "region"