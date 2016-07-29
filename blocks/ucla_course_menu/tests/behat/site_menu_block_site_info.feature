@ucla @block_ucla_course_menu
Feature: "Site info" section in Site menu block
  In order to get an overview of the course
  As a user
  I want to see registrar information and default forums

  Background:
    Given I am in a ucla environment
    And the following ucla "sites" exist:
      | fullname | shortname | type |
      | course 1 | C1 | srs |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
      | student1 | Student | 1 | student1@asd.com |
    And the following ucla "enrollments" exist:
      | user | course | role |
      | teacher1 | C1 | editinginstructor |
      | student1 | C1 | student |
    And I log in as "student1"
    And I browse to site "C1"

  Scenario: "Site info" works
    Then I should see "Site info" in the ucla site menu
    When I follow the "Site info" section in the ucla site menu
    # Registrar info.
    Then I should see "For course location and time see Registrar Listing" in the "region-main" "region"
    # Office hours.
    And I should see "1, Teacher" in the "region-main" "region"
    # Default forums.
    And I should see "Announcements" in the "region-main" "region"
    And I should see "Discussion forum" in the "region-main" "region"