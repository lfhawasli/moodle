@ucla @enrol @core_edit @CCLE-5251
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
      | username | firstname | lastname | email |
      | teacher1 | Teacher | T1 | teacher1@asd.com |
      | student1 | Student | S1 | student1@asd.com |
      | student2 | Student | S2 | student2@asd.com |
      | student3 | Student | S3 | student3@asd.com |
    And the following ucla "roles" exist:
      | role               |
      | projectparticipant |
      | editinginstructor  |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editinginstructor |
      | student1 | C1 | projectparticipant |
      | student2 | C1 | student |
      | student3 | C1 | student |
    And I log in as "teacher1"
    And I follow "Course 1"

Scenario: Show number of displayed users in renamed page.
    When I navigate to "Participants" node in "Course administration > Users"
    Then I should see "Participants: 4/4" in the "#page" "css_element"
    And I should not see "Enrolled users"

Scenario: View user details.
    Given I navigate to "Participants" node in "Course administration > Users"
    Then I should see "Activity" in the "userenrolment" "table"
    And I should not see "Roles" in the "userenrolment" "table"
    And I should not see "Enrolment methods" in the "userenrolment" "table"
    But I should not see "Log in as" in the "userenrolment" "table"
    When I log out
    And I log in as "admin"
    And I follow "Course 1"
    And I navigate to "Participants" node in "Course administration > Users"
    Then I should see "Activity" in the "userenrolment" "table"
    And I should see "Log in as" in the "userenrolment" "table"
    When I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I navigate to "Participants" node in "Course administration > Users"
    Then I should not see "Groups" in the "userenrolment" "table"
    And I should not see "student3@asd.com" in the "userenrolment" "table"
