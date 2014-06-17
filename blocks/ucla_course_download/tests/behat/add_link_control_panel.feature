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

  Scenario: Control Panel link for students
    Given it is "9th" week
    And I log in as ucla "student1"
    And I browse to site "C1"
    When I press "Control Panel"
    Then I should see "Download course materials" in the "region-main" "region"
    When I follow "Download course materials"
    Then I should see "UCLA course download" in the "region-main" "region"

  Scenario: Control Panel link for instructors
    Given I log in as ucla "teacher1"
    And I browse to site "C1"
    When I press "Control Panel"
    Then I should see "Download course materials" in the "region-main" "region"
    When I follow "Download course materials"
    Then I should see "UCLA course download" in the "region-main" "region"


