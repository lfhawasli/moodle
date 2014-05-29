@ucla @block_ucla_course_download
Feature: Alert students about course download feature
  As a student
  I want to be alerted that I can use the course download feature
  So that I can keep a copy of my course material

  Background:
    Given I am in a ucla environment
    And the following "users" exists:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@asd.com |
    And the following ucla "sites" exists:
        | fullname | shortname | type |
        | course 1 | C1 | srs |
    And the following ucla "enrollments" exists:
      | user | course | role |
      | student1 | C1 | student |

  @javascript
  Scenario: Alert students
    And I log in as ucla "student1"
    And I browse to site "C1"
    And I should see "For copyright compliance you will no longer have access to this course site 2 weeks into the next term" in the "region-main" "region"
    And I should see "Download course content" in the "region-main" "region"
    And I press "Download course content"
    Then I should see "UCLA course download" in the "region-main" "region"