@ucla @enrol_invitation @CCLE-4476
Feature: Resend an invite
  In order to send an invitation to student whose invitation has expired or revoked
  As an instructor or project lead
  I want to be able to resend an invite

  Scenario: Resend invitation
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
    And I follow "Invite history"
    And I follow "Revoke invite"
    When I follow "Resend invite"
    Then I should see "What role do you want to assign to the invitee?"
