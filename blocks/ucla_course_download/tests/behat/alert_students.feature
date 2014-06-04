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

  Scenario: Alert students in a course during 9th week.
    Given it is "9th" week
    And I log in as ucla "student1"
    When I browse to site "C1"
    Then I should see "For copyright compliance you will no longer have access to this course site 2 weeks into the next term" in the "region-main" "region"
    And I should see "Download course content" in the "region-main" "region"
    # Verify that button brings you to appropiate page.
    When I press "Download course content"
    Then I should see "UCLA course download" in the "region-main" "region"
    # Verify that clicking on button doesn't display prompt anymore.
    When I am on homepage
    And I browse to site "C1"
    Then I should not see "For copyright compliance you will no longer have access to this course site 2 weeks into the next term" in the "region-main" "region"

  Scenario: Do not alert students in a course during 6th week.
    Given it is "6th" week
    And I log in as ucla "student1"
    When I browse to site "C1"
    Then I should not see "For copyright compliance you will no longer have access to this course site 2 weeks into the next term" in the "region-main" "region"

  Scenario: Alert students for an old course.
    # Default term that a course is built for is 14W.
    Given it is "Spring 2014" term "1th" week
    And I log in as ucla "student1"
    When I browse to site "C1"
    Then I should see "For copyright compliance you will no longer have access to this course site 2 weeks into the next term" in the "region-main" "region"
    # Verify that clicking on "Dismiss" generates message.
    When I press "Dismiss"
    Then I should see "You will no longer be prompted to download course material. Use the Download Course Content link in the Control Panel to request content later." in the "region-main" "region"
    # Verify that clicking on "Dismiss" doesn't display prompt anymore.
    When I am on homepage
    And I browse to site "C1"
    Then I should not see "For copyright compliance you will no longer have access to this course site 2 weeks into the next term" in the "region-main" "region"
    
