@ucla @block_ucla_modify_coursemenu
Feature: Setting a landing page
  As an instructor
  I want to set the landing page for my course
  So that I can guide my students to the appropiate starting page

  Background: UCLA environment and srs site exists
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

  @javascript @local_ucla_syllabus
  Scenario: Set syllabus as a landing page
    Given I log in as ucla "teacher1"
    And I browse to site "C1"
    And I turn editing mode on
    And I follow "Modify sections"
    And I wait "2" seconds
    And I select "landing-page-syllabus" radio button
    And I press "Save changes"
    And I should see "The sections have been successfully updated."
    When I press "Return to course"
    Then I should see "Syllabus manager"
    And I log out
    And I log in as ucla "student1"
    And I browse to site "C1"
    And I should see "Syllabus is not available yet"