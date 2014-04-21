@ucla @block_ucla_course_menu
Feature: "Site info" section in Site menu block
    In order to get an overview of the course
    As a user
    I want to see registrar information and default forums.

Scenario: "Site info" works.
    Given I am in a ucla environment
    And a ucla "srs" site exists
    And I log in as "student"
    When I go to the default ucla site
    Then I should see "Site info" in the ucla site menu
    When I follow the "Site info" section in the ucla site menu
    # Registrar info.
    Then I should see "For course location and time see Registrar Listing" in the "region-main" "region"
    # Office hours.
    And I should see "Instructor, Editing" in the "region-main" "region"
    # Default forums.
    And I should see "Announcements" in the "region-main" "region"
    And I should see "Discussion forum" in the "region-main" "region"

