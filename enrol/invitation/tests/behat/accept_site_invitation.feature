@ucla @enrol_invitation @CCLE-4476
Feature: Site invitations
  In order to use the enrolment invitation plugin
  As an instructor
  I want to have enrolment be restricted to unexpired tokens and to the role specified

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
    And I am on "Course 1" course homepage
    And I follow "Admin panel"
    And I follow "Invite users"
    And I set the following fields to these values:
      | <rolename> | 1 |
      | Email address | receiv@asd.com |
    And I press "Invite users"
    And I log out
    And I log in as "receiver"
    And I follow the link in the last invitation sent to "receiver" for site "Course 1"
    Then I should see "receiv@asd.com"
    And I should see <rolename2>
    And I press "Accept invitation"
    Then I should see "Course 1"
    When I follow the link in the last invitation sent to "receiver" for site "Course 1"
    Then I should see "Site invitation token is expired or has already been used."
    
    Examples:
      | type | role | inviterole | rolename | rolename2 |
      | class | editingteacher | editor | Editor | "Editor" |
      | non_instruction | projectlead | projectparticipant | Project Participant | "Project Participant" |