@ucla @core_course @core_edit @CCLE-4415
Feature: Deleting a course with additional course content
  In order to delete a course
  As a moodle admin
  I need to be able to see if I am deleting a course with additional course content

  Background:
    Given the following "users" exists:
      | username | firstname | lastname | email |
      | teacher | Teacher | 1 | teacher@asd.com |
    Given the following "categories" exists:
      | name       | category | idnumber |
      | Category 1 | 0        | CAT1     |
      | Category 2 | CAT1     | CAT2     |
    Given the following "courses" exists:
      | fullname | shortname | category | format | 
      | Course 1 | COURSE1   | CAT2     | ucla   |
      | Course 2 | COURSE2   | CAT2     | topics |
      | Course 3 | COURSE3   | CAT2     | weeks  |
     Given the following "course enrolments" exists:
      | user | course | role |
      | teacher | COURSE1 | editingteacher |
      | teacher | COURSE2 | editingteacher |
      | teacher | COURSE3 | editingteacher |
     And I log in as administrator

  @javascript
  Scenario: Deleting a course with a block for all 3 course formats
    # UCLA format.
    When I follow "Course 1"
    And I press "Turn editing on"
    And I reload the page
    And I add the "Calendar" block
    Then I should see "Events key" in the ".block_calendar_month" "css_element"
    When I expand "Site administration" node
    And I expand "Courses" node
    And I follow "Add/edit courses"
    And I follow "Category 2"
    And I click on "Delete" "link" in the "Course 1" "table_row"
    Then I should see "WARNING" in the "region-main" "region"
    And I should see "You are deleting a course for which content has been added." in the "region-main" "region"
    And I am on homepage
    # Topics format.
    When I follow "Course 2"
    And I add the "Calendar" block
    Then I should see "Events key" in the ".block_calendar_month" "css_element"
    When I expand "Site administration" node
    And I expand "Courses" node
    And I follow "Add/edit courses"
    And I follow "Category 2"
    And I click on "Delete" "link" in the "Course 2" "table_row"
    Then I should see "WARNING" in the "region-main" "region"
    And I should see "You are deleting a course for which content has been added." in the "region-main" "region"
    And I am on homepage
    # Weeks format.
    When I follow "Course 3"
    And I add the "Calendar" block
    Then I should see "Events key" in the ".block_calendar_month" "css_element"
    When I expand "Site administration" node
    And I expand "Courses" node
    And I follow "Add/edit courses"
    And I follow "Category 2"
    And I click on "Delete" "link" in the "Course 3" "table_row"
    Then I should see "WARNING" in the "region-main" "region"
    And I should see "You are deleting a course for which content has been added." in the "region-main" "region"

  @javascript
  Scenario: Deleting a course with default forum activity in UCLA format.
    Given I log out
    And I log in as "teacher"
    And I follow "Course 1"
    And I add a new discussion to "Discussion forum" forum with:
      | Subject | Test Subject |
      | Message | Test Message |
    And I should see "Test Subject"
    And I log out
    And I log in as administrator
    Then I expand "Site administration" node
    And I expand "Courses" node
    And I follow "Add/edit courses"
    And I follow "Category 2"
    And I click on "Delete" "link" in the "Course 1" "table_row"
    Then I should see "WARNING" in the "region-main" "region"
    And I should see "You are deleting a course for which content has been added." in the "region-main" "region"

  @javascript
  Scenario: Deleting a course with a module for all 3 formats.
    # UCLA format.
    Given I log out
    And I log in as "teacher"
    And I follow "Course 1"
    And I press "Turn editing on"
    And I add a "Quiz" to section "0" and I fill the form with:
      | Name | Test Quiz |
    And I should see "Test Quiz" in the "region-main" "region"
    And I log out
    And I log in as administrator
    Then I expand "Site administration" node
    And I expand "Courses" node
    And I follow "Add/edit courses"
    And I follow "Category 2"
    And I click on "Delete" "link" in the "Course 1" "table_row"
    Then I should see "WARNING" in the "region-main" "region"
    And I should see "You are deleting a course for which content has been added." in the "region-main" "region"
    # Topics format.
    Given I log out
    And I log in as "teacher"
    And I follow "Course 2"
    And I press "Turn editing on"
    And I add a "Quiz" to section "0" and I fill the form with:
      | Name | Test Quiz |
    And I should see "Test Quiz" in the "region-main" "region"
    And I log out
    And I log in as administrator
    Then I expand "Site administration" node
    And I expand "Courses" node
    And I follow "Add/edit courses"
    And I follow "Category 2"
    And I click on "Delete" "link" in the "Course 2" "table_row"
    Then I should see "WARNING" in the "region-main" "region"
    And I should see "You are deleting a course for which content has been added." in the "region-main" "region"
    # Weeks format.
    Given I log out
    And I log in as "teacher"
    And I follow "Course 3"
    And I press "Turn editing on"
    And I add a "Quiz" to section "0" and I fill the form with:
      | Name | Test Quiz |
    And I should see "Test Quiz" in the "region-main" "region"
    And I log out
    And I log in as administrator
    Then I expand "Site administration" node
    And I expand "Courses" node
    And I follow "Add/edit courses"
    And I follow "Category 2"
    And I click on "Delete" "link" in the "Course 3" "table_row"
    Then I should see "WARNING" in the "region-main" "region"
    And I should see "You are deleting a course for which content has been added." in the "region-main" "region"

  @javascript
  Scenario: Deleting a course with no additional course content for all 3 formats.
    # UCLA format.
    When I follow "Course 1"
    And I expand "Site administration" node
    And I expand "Courses" node
    And I follow "Add/edit courses"
    And I follow "Category 2"
    And I click on "Delete" "link" in the "Course 1" "table_row"
    Then I should see "You are deleting a course for which no content has been added." in the "region-main" "region"
    And I am on homepage
    # Topics format.
    When I follow "Course 2"
    And I expand "Site administration" node
    And I expand "Courses" node
    And I follow "Add/edit courses"
    And I follow "Category 2"
    And I click on "Delete" "link" in the "Course 2" "table_row"
    Then I should see "You are deleting a course for which no content has been added." in the "region-main" "region"
    And I am on homepage
    # Weeks format.
    When I follow "Course 3"
    And I expand "Site administration" node
    And I expand "Courses" node
    And I follow "Add/edit courses"
    And I follow "Category 2"
    And I click on "Delete" "link" in the "Course 3" "table_row"
    Then I should see "You are deleting a course for which no content has been added." in the "region-main" "region"


