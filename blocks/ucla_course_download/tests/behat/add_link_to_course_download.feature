@ucla @block_ucla_course_download @CCLE-4758
Feature: Add link to course download
  As a student or instructor
  I want to download a zip file containing all course content
  So that I reference it after the course is complete

  Background:
    Given I am in a ucla environment
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
      | student1 | Student | 1 | student1@asd.com |
    And the following ucla "sites" exist:
      | fullname | shortname | type |
      | Course 1 | C1 | srs |
      | Collab test | CT | test |
    And the following ucla "enrollments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | teacher1 | CT | editingteacher |
      | student1 | CT | student |

  Scenario: Instructor turned off course download and students cannot access course download link.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Admin panel"
    And I follow "Edit settings"
    And I expand all fieldsets
    When I set the field "Enable download course materials" to "No"
    And I press "Save and display"
    And I follow "Admin panel"
    And I follow "Download course material"
    Then I should see "Students do not have access to this feature." in the "region-main" "region"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should not see "Download course material"

  Scenario: Course download is active and students can access Admin panel link.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Admin panel"
    And I follow "Edit settings"
    And I expand all fieldsets
    When I set the field "Enable download course materials" to "Yes"
    And I press "Save and display"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "Download course material" in the "region-main" "region"
    When I follow "Download course material"
    Then I should see "Download course material" in the "page-header" "region"

  Scenario: Admin panel link for instructors
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    When I follow "Admin panel"
    Then I should see "Download course material" in the "region-main" "region"
    When I follow "Download course material"
    Then I should see "Download course material" in the "page-header" "region"

  Scenario: Instructor turned off course download in collab site
    Given I log in as "teacher1"
    And I am on "Collab test" course homepage
    And I follow "Admin panel"
    And I follow "Edit settings"
    And I expand all fieldsets
    When I set the field "Enable download course materials" to "No"
    And I press "Save and display"
    And I follow "Admin panel"
    And I follow "Download course material"
    Then I should see "Students do not have access to this feature." in the "region-main" "region"
    When I log out
    And I log in as "student1"
    And I am on "Collab test" course homepage
    Then I should not see "Download course material"

  Scenario: Course download is active in collab site
    Given I log in as "teacher1"
    And I am on "Collab test" course homepage
    And I follow "Admin panel"
    And I follow "Edit settings"
    And I expand all fieldsets
    When I set the field "Enable download course materials" to "Yes"
    And I press "Save and display"
    And I log out
    And I log in as "student1"
    And I am on "Collab test" course homepage
    Then I should see "Download course material"
    When I follow "Download course material"
    Then I should see "Download course material" in the "page-header" "region"
