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

  Scenario: View and approve a user-submitted site request
    Given I set the following fields to these values:
      | id_indicator_type_non_instruction | 1                          |
      | Site category                     | Category 1                 |
      | Site full name                    | Test collab request course |
      | Site short name                   | testcollreq                |
      | id_reason                         | because                    |
    When I press "Request a collaboration site"
    And I should see "Your collaboration site request has been submitted."
    And I am on homepage
    And I navigate to "Pending requests" node in "Site administration > Courses"
    And I should see "testcollreq"
    And I press "Approve"
    # Make sure site type is saved.
    Then I should see "Other" in the "fitem_id_indicator" "region"
    And I press "Save and display"
    Then I should see "Test collab request course"
    # Requester gets added to course.
    And I should see "Requester, Course"
    When I navigate to "Pending requests" node in "Site administration > Courses"
    Then I should not see "Test collab request course"
    When I navigate to "Manage courses and categories" node in "Site administration > Courses"
    And I click on category "Category 1" in the management interface
    Then I should see "Test collab request course"

  Scenario: View and reject a user-submitted site request
    Given I set the following fields to these values:
      | id_indicator_type_instruction | 1                          |
      | Site category                 | Category 1                 |
      | Site full name                | Test collab request course |
      | Site short name               | testcollreq                |
      | id_reason                     | because                    |
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
