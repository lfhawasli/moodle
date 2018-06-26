@ucla @block_ucla_modify_coursemenu
Feature: Setting a landing page
  As an instructor
  I want to set the landing page for my course
  So that I can guide my students to the appropiate starting page

  Background: UCLA environment and srs site exist
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

  @javascript @local_ucla_syllabus
  Scenario: Set syllabus as a landing page
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I follow "Modify sections"
    And I wait "2" seconds
    And I set the field "landing-page-syllabus" to "1"
    And I press "Save changes"
    And I should see "Success!"
    And I log out
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    Then I should see "Syllabus manager"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "Syllabus is not available yet"

  @javascript
  Scenario: Setting landing page and turning editing mode on/off 
            stays on correct section
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I follow "Modify sections"
    And I wait "2" seconds
    # Select Week 4
    And I set the field "landing-page-4" to "1"
    And I press "Save changes"
    Then I should see "Success!"
    And I press "Return to site"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    # Must be redirected to new landing page
    Then I should be on section "Week 4"
    And I turn editing mode on
    Then I should be on section "Week 4"
    And I turn editing mode off
    Then I should be on section "Week 4"
    # Check another week
    And I follow "Week 7"
    And I turn editing mode on
    Then I should be on section "Week 7"
    And I turn editing mode off
    Then I should be on section "Week 7"
    # Check site info
    And I follow "Site info"
    And I turn editing mode on
    Then I should be on section "Site info"
    And I turn editing mode off
    Then I should be on section "Site info"
    # Check Show all
    And I follow "Show all"
    And I turn editing mode on
    Then I should be on section "Show all"
    And I turn editing mode off
    Then I should be on section "Show all"
