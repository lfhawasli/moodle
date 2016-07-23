@ucla @core_edit @core_course @tool_uclasiteindicator @TESTINGCCLE-1484 @CCLE-4341
Feature: Approving collab sites as a Manager Limited
  In order to approve and edit a requested course in my category
  As a Manager Limited
  I want the course to stay in the same category so I have permission to edit it

  Background: 
    Given I am in a ucla environment
    And the following "users" exist:
      | username  | lastname  | firstname |
      | requester | Requester | Course    |
      | mglimited | Manager   | Limited   |
    And the following ucla "roles" exist:
      | role              |
      | editinginstructor |
      | projectlead       |
      | manager_limited   |
    And the following "categories" exist:
      | idnumber | name       |
      | CAT1     | Category 1 |
    When I log in as "requester"
    And I am on "course/request.php"
    And I set the following fields to these values:
      | indicator_type  | instruction                |
      | Site category   | Category 1                 |
      | Site full name  | Test collab request course |
      | Site short name | testcollreq                |
      | id_reason       | because                    |
    And I press "Request a collaboration site"
    And I log out

  Scenario: Approve a site-request as a Manager of that category and be able to edit
    Given the following "role assigns" exist:
      | user      | role            | contextlevel | reference |
      | mglimited | manager_limited | System       |           |
      | mglimited | manager         | Category     | CAT1      |
    When I log in as "mglimited"
    And I navigate to "Pending requests" node in "Site administration > Courses"
    And I should see "Test collab request course"
    And I press "Approve"
    And the field "Course category" matches value "Category 1"
    And I press "Save and display"
    And I should see "Test collab request course"

  Scenario: Do not allow approval of a site-request when not a Manager of that category.
    Given the following "categories" exist:
      | idnumber | name       |
      | CAT2     | Category 2 |
    And the following "role assigns" exist:
      | user      | role            | contextlevel | reference |
      | mglimited | manager_limited | System       |           |
      | mglimited | manager         | Category     | CAT2      |
    When I log in as "mglimited"
    And I navigate to "Pending requests" node in "Site administration > Courses"
    Then I should not see "Test collab request course"
    And I should not see "Approve"
