@ucla @local_ucla_syllabus
Feature: Links in site menu block
    As a user
    I want to be able to view the syllabus link
    So that I can view and download the syllabus if it is available

Background:
   Given I am in a ucla environment
   And the following "users" exist:
       | username | firstname | lastname | email |
       | teacher1 | Teacher | 1 | teacher1@asd.com |
       | student1 | Student | 1 | student1@asd.com |
   And the following ucla "sites" exist:
       | fullname | shortname | type |
       | Course 1 | C1 | srs |
   And the following ucla "enrollments" exist:
       | user | course | role |
       | teacher1 | C1 | editingteacher |
       | student1 | C1 | student |
   And I log in as "teacher1"
   And I am on "Course 1" course homepage
   And I turn editing mode on
   And I follow the "Syllabus (empty)" section in the ucla site menu
   And I follow "Add syllabus"
   And I upload "lib/tests/fixtures/empty.txt" file to "File" filemanager
   And I set the field "Display name" to "Test Syllabus"
   And I press "Save changes"
   And I log out

@javascript
Scenario: Link in site menu block as student
    Given I log in as "student1"
    When I follow "Course 1"
    Then I should see "Test Syllabus" in the ucla site menu
    When I follow the "Test Syllabus" section in the ucla site menu
    Then I should see "Test Syllabus" in the "region-main" "region"
    And I should see "Download: Test Syllabus" in the "region-main" "region"

@javascript
Scenario: Link in site menu block as instructor
    Given I log in as "teacher1"
    When I follow "Course 1"
    Then I should see "Test Syllabus" in the ucla site menu
    When I follow the "Test Syllabus" section in the ucla site menu
    Then I should see "Test Syllabus" in the "region-main" "region"
    And I should see "Download: Test Syllabus" in the "region-main" "region"