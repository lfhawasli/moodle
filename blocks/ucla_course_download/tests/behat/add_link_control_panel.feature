@ucla @block_ucla_course_download @CCLE-4758
Feature: Add link to Control Panel
  As a student or instructor
  I want to download a zip file containing all course content
  So that I reference it after the course is complete

  Background:
    Given I am in a ucla environment
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
      | student1 | Student | 1 | student1@asd.com |
      | student2 | Student | 2 | student2@asd.com |
    And the following ucla "sites" exist:
      | fullname | shortname | type |
      | course 1 | C1 | srs |
      | collab test | CT | test |
    And the following ucla "enrollments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | teacher1 | CT | editingteacher |
      | student1 | CT | student |
      | student2 | CT | projectparticipant |

  Scenario: Instructor turned off course download and students cannot access Control Panel link.
    Given I log in as "teacher1"
    And I follow "course 1"
    And I follow "Edit settings"
    And I expand all fieldsets
    When I set the field "Download course materials" to "No"
    And I press "Save and display"
    And I press "Control Panel"
    Then I should see "Disabled for students" in the "region-main" "region"
    And I log out
    And it is "9th" week
    And I log in as "student1"
    And I follow "course 1"
    And I press "Control Panel"
    Then I should see "Not available for this course" in the "region-main" "region"

  Scenario: Course download is active and students can access Control Panel link.
    Given I log in as "teacher1"
    And I follow "course 1"
    And I follow "Edit settings"
    And I expand all fieldsets
    When I set the field "Download course materials" to "Yes"
    And I press "Save and display"
    And I log out
    And it is "3rd" week
    And I log in as "student1"
    And I follow "course 1"
    And I press "Control Panel"
    Then I should see "Download course materials" in the "region-main" "region"
    When I follow "Download course materials"
    Then I should see "UCLA course download" in the "region-main" "region"

  Scenario: Control Panel link for instructors
    Given I log in as "teacher1"
    And I follow "course 1"
    When I press "Control Panel"
    Then I should see "Download course materials" in the "region-main" "region"
    When I follow "Download course materials"
    Then I should see "UCLA course download" in the "region-main" "region"

  Scenario Outline: Instructor turned off course download in collab site
    Given I log in as "teacher1"
    And I follow "collab test"
    And I follow "Edit settings"
    And I expand all fieldsets
    When I set the field "Download course materials" to "No"
    And I press "Save and display"
    And I press "Control Panel"
    Then I should see "Disabled for students" in the "region-main" "region"
    When I log out
    And I log in as "<student>"
    And I follow "collab test"
    And I press "Control Panel"
    Then I should see "Not available for this course" in the "region-main" "region"
    Examples:
      | student  |
      | student1 |
      | student2 |

  Scenario Outline: Course download is active in collab site
    Given I log in as "teacher1"
    And I follow "collab test"
    And I follow "Edit settings"
    And I expand all fieldsets
    When I set the field "Download course materials" to "Yes"
    And I press "Save and display"
    And I log out
    And I log in as "<student>"
    And I follow "collab test"
    And I press "Control Panel"
    Then I should see "Download course materials" in the "region-main" "region"
    When I follow "Download course materials"
    Then I should see "UCLA course download" in the "region-main" "region"
    Examples:
      | student  |
      | student1 |
      | student2 |
