@ucla @enrol_invitation @CCLE-4476
Feature: Revoke an invite
  In order to revoke an invitation that is already sent
  As an instructor or project lead
  I want to be able to revoke an invite

  Scenario: Revoke invitation
    Given I am in a ucla environment
    And the following "users" exist:
     | username | firstname | lastname | email |
     | teacher1 | Teacher | 1 | teacher1@asd.com |
    And the following ucla "sites" exist:
     | fullname | shortname | type |
     | course 1 | C1 | srs |
    And the following ucla "enrollments" exist:
     | user | course | role |
     | teacher1 | C1 | editingteacher | 
    And the following ucla "roles" exist:
      | role |
      | editor |
    And I log in as "teacher1"
    And I browse to site "C1"
    And I follow "Admin panel"
    And I follow "Invite users"
    And I set the following fields to these values:
      | role_group[roleid] | 9 |
      | Email address | s1@asd.com |
      | Subject | Site invitation to course 1 |
    And I press "Invite users"
    And I should see "Invitation successfully sent"
    When I follow "Invite history"
    And I follow "Revoke invite"
    Then the following should exist in the "generaltable" table:
      | Status | 
      | Revoked |
