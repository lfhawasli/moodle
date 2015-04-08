@ucla @enrol_invitation @CCLE-4476
Feature: Site invitations
  In order to limit invitation enrolment
  As an instructor
  I want to have enrolment prevented from a previously used invitation

  Scenario Outline:
    Given I am in a ucla environment
    And the following ucla "sites" exist:
      | shortname | fullname | type |
      | COURSE1 | Course 1 | <type> |
    And the following "users" exist:
      | username | email |
      | sender | send@asd.com |
      | receiver | receiv@asd.com |
    And the following ucla "course enrolments" exist:
      | user | course | role |
      | sender | COURSE1 | <role> |
    And the following ucla "roles" exist:
      | role |
      | <inviterole> |
    When I log in as "sender"
    And I follow "Course 1"
    And I press "Control Panel"
    And I follow "Invite users"
    And I set the following fields to these values:
      | role_group[roleid] | <roleid> |
      | Email address | receiv@asd.com |
    And I press "Invite users"
    And I log out
    And I log in as "receiver"
    And I follow the link in the last invitation sent to "receiver" for ucla site "COURSE1"
    And I press "Accept invitation"
    Then I should see "Course 1"
    When I follow the link in the last invitation sent to "receiver" for ucla site "COURSE1"
    Then I should see "Site invitation token is expired or has already been used."
    
    Examples:
      | type | role | inviterole | roleid  |
      | class | editingteacher | editor | 28 |
      | non_instruction | projectlead | projectparticipant | 34 |