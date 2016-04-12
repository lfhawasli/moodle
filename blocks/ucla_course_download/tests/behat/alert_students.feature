@ucla @block_ucla_course_download
Feature: Alert students about course download feature
  As a student
  I want to be alerted that I can use the course download feature
  So that I can keep a copy of my course material

  Background:
    Given I am in a ucla environment
    And the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@asd.com |
    And the following ucla "sites" exist:
        | fullname | shortname | type |
        | course 1 | C1 | srs |
    And the following ucla "enrollments" exist:
      | user | course | role |
      | student1 | C1 | student |

  Scenario: Alert students in a course during 3rd week.
    Given it is "3rd" week
    And I log in as "student1"
    When I follow "course 1"
    Then I should see "Download course materials" in the "region-main" "region"
    # Verify that button brings you to appropriate page.
    When I press "Download course materials"
    Then I should see "UCLA course download" in the "region-main" "region"
    # Verify that clicking on button doesn't display prompt anymore.
    When I am on homepage
    And I follow "course 1"
    Then I should not see "Download course materials" in the "region-main" "region"

  Scenario: Students see link in control panel during 10th week.
    Given it is "10th" week
    And I log in as "student1"
    When I follow "course 1"
    And I press "Control Panel"
    Then I should see "Download course materials" in the "region-main" "region"
    And I follow "Download course materials"
    And I should see "UCLA course download" in the "region-main" "region"

   Scenario: Alert students for an old course.
     # Default term that a course is built for is 14W.
     Given it is "Spring 2014" term "1st" week
     And I log in as "student1"
     When I follow "course 1"
     Then I should see "Download course materials" in the "region-main" "region"
     # Verify that clicking on "Dismiss" generates message.
     When I press "Dismiss"
     Then I should see "You will no longer be prompted to download course material." in the "region-main" "region"
     # Verify that clicking on "Dismiss" doesn't display prompt anymore.
     When I am on homepage
     And I follow "course 1"
     Then I should not see "Download course materials" in the "region-main" "region"

