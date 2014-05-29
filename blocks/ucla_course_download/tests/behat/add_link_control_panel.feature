@ucla @block_ucla_course_download
Feature: Add link to Control Panel
  As a student or instructor
  I want to download a zip file containing all course content
  So that I reference it after the course is complete

  Background:
    Given I am in a ucla environment
    And the following "users" exists:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
      | student1 | Student | 1 | student1@asd.com |
    And the following ucla "sites" exists:
        | fullname | shortname | type |
        | course 1 | C1 | srs |
    And the following ucla "enrollments" exists:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |

  @javascript
  Scenario: Control Panel link for students
    And I log in as ucla "student1"
    And I browse to site "C1"
    And I press "Control Panel"
    And I should see "Download course content" in the "region-main" "region"
    And I follow "Download course content"
    Then I should see "UCLA course download" in the "region-main" "region"

  @javascript
  Scenario: Control Panel link for instructors
    And I log in as ucla "teacher1"
    And I browse to site "C1"
    And I press "Control Panel"
    And I should see "Download course content" in the "region-main" "region"
    And I follow "Download course content"
    Then I should see "UCLA course download" in the "region-main" "region"


