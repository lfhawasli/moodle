@ucla @local_ucla_syllabus 
Feature: Convert syllabus from public to private and vice versa
  As an instructor
  I want to be able to restrist and unrestrict a course syllabus
  So that I can control who can view the syllabus

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
  Scenario: Convert syllabus from public to private and vice versa
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I follow "Syllabus (empty)"
    And I follow "Add syllabus"
    And I upload "lib/tests/fixtures/empty.txt" file to "File" filemanager
    And I set the field "Display name" to "Test Syllabus"
    And I set the field "UCLA community (login required)" to "1"
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