@ucla @core_edit @core_course @tool_uclasiteindicator @TESTINGCCLE-1484
Feature: Requesting collab sites
  In order to create a collab site
  As a manager limited
  I want to be able to request a collab site and then have it be approved/rejected successfully by an admin

  Background: 
    Given I am in a ucla environment
    And the following "users" exist:
      | username  | lastname  | firstname |
      | requester | Requester | Course    |
    And the following ucla "roles" exist:
      | role              |
      | editinginstructor |
      | projectlead       |
      | manager_limited   |
    And the following "categories" exist:
      | idnumber | name       |
      | CAT1     | Category 1 |
    And the following ucla "role assigns" exist:
      | user      | role            | contextlevel | reference |
      | requester | manager_limited | System       |           |
      | requester | manager         | Category     | CAT1      |
    And I log in as "requester"
    And I am on "course/request.php"

  Scenario: Submit a site request and then view it
    Given I set the following fields to these values:
      | indicator_type  | instruction                |
      | Site category   | Category 1                 |
      | Site full name  | Test collab request course |
      | Site short name | testcollreq                |
      | id_reason       | because                    |
    When I press "Request a collaboration site"
    And I should see "Your collaboration site request has been submitted."
    And I press "Continue"
    And I expand "Site administration" node
    And I follow "Pending requests"
    Then I should see "testcollreq"

  Scenario Outline: View and approve a user-submitted site request
    Given I set the following fields to these values:
      | indicator_type  | <type>                     |
      | Site category   | Category 1                 |
      | Site full name  | Test collab request course |
      | Site short name | testcollreq                |
      | id_reason       | because                    |
    When I press "Request a collaboration site"
    And I should see "Your collaboration site request has been submitted."
    And I am on homepage
    And I navigate to "Pending requests" node in "Site administration > Courses"
    And I press "Approve"
    And I press "Save changes"
    Then I should see "Test collab request course"
    And I should see "Requester, Course"
    When I navigate to "Pending requests" node in "Site administration > Courses"
    Then I should not see "Test collab request course"
    When I navigate to "Manage courses and categories" node in "Site administration > Courses"
    And I click on category "Category 1" in the management interface
    Then I should see "Test collab request course"

    Examples: 
      | type            |
      | instruction     |
      | non_instruction |
      | research        |
      | test            |
      | private         |

  Scenario: View and reject a user-submitted site request
    Given I set the following fields to these values:
      | indicator_type  | instruction                |
      | Site category   | Category 1                 |
      | Site full name  | Test collab request course |
      | Site short name | testcollreq                |
      | id_reason       | because                    |
    And I press "Request a collaboration site"
    And I should see "Your collaboration site request has been submitted."
    And I am on homepage
    And I navigate to "Pending requests" node in "Site administration > Courses"
    And I press "Reject..."
    And I press "submitbutton"
    And I should see "Site has been rejected"
    When I am on homepage
    Then I should not see "Test collab request course"
    When I navigate to "Pending requests" node in "Site administration > Courses"
    Then I should not see "Test collab request course"
    And I should see "There are no collaboration site requests pending approval"
