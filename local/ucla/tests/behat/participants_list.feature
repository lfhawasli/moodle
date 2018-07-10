@ucla @local_ucla @enrol @core_edit @CCLE-5251
Feature: Combine "Enrolled Users" and "Participants" pages
  As an instructor
  I want to list participant information on one page
  so that I can manage it more efficiently.

Background:
    Given I am in a ucla environment
    And the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email             | alternatename |
      | teacher  | Teacher   | T1       | teacher1@asd.com  | edward        |
      | manager  | Manager   | M3       | manager1@asd.com  | jenny         |
      | projpart | Projpart  | P1       | projpart1@asd.com | tom           |
      | student  | Student   | S1       | student1@asd.com  | victor        |
    And the following ucla "roles" exist:
      | role               |
      | editinginstructor  |
      | manager            |
      | projectparticipant |
      | student            |
    And the following "course enrolments" exist:
      | user     | course | role               |
      | teacher  | C1     | editinginstructor  |
      | manager  | C1     | manager            |
      | projpart | C1     | projectparticipant |
      | student  | C1     | student            |

@CCLE-5317
Scenario: Show number of displayed users.
    Given I log in as "teacher"
    And I am on "Course 1" course homepage
    When I navigate to "Participants" node in "Course administration > Users"
    Then I should see "Participants: 4" in the "#page" "css_element"
    When I set the field "Role" to "Instructor"
    And I press "Filter"
    Then I should see "Participants: 1" in the "#page" "css_element"
    When I press "Reset"
    # First initial: T.
    And I click on "//*[@id='region-main']/div/form[1]/div[1]/div[1]/a[20]" "xpath_element"
    Then I should see "Participants: 1" in the "#page" "css_element"

@CCLE-5320
Scenario: Limited views.
    # Instructor.
    Given I log in as "teacher"
    And I am on "Course 1" course homepage
    When I navigate to "Participants" node in "Course administration > Users"
    Then I should see "Activity" in the "userenrolment" "table"
    And I should not see "Log in as" in the "userenrolment" "table"
    And I should see "Roles" in the "userenrolment" "table"
    And I should see "Groups" in the "userenrolment" "table"
    And I should see "Enrolment methods" in the "userenrolment" "table"
    And I log out
    # Manager.
    When I log in as "manager"
    And I am on "Course 1" course homepage
    And I navigate to "Participants" node in "Course administration > Users"
    Then I should see "Activity" in the "userenrolment" "table"
    And I should see "Log in as" in the "userenrolment" "table"
    And I should see "Roles" in the "userenrolment" "table"
    And I should see "Groups" in the "userenrolment" "table"
    And I should see "Enrolment methods" in the "userenrolment" "table"
    And I log out
    # Project participant.
    When I log in as "projpart"
    And I am on "Course 1" course homepage
    And I navigate to "Participants" node in "Course administration > Users"
    Then I should see "Last access to course" in the "userenrolment" "table"
    And I should not see "Activity" in the "userenrolment" "table"
    And I should not see "Log in as" in the "userenrolment" "table"
    And I should not see "Roles" in the "userenrolment" "table"
    And I should not see "Groups" in the "userenrolment" "table"
    And I should not see "Enrolment methods" in the "userenrolment" "table"
    And I should not see "student1@asd.com" in the "userenrolment" "table"
    And I should not see "Status"
    And I log out
    # Student.
    When I log in as "student"
    And I am on "Course 1" course homepage
    Then I should not see "Users"
