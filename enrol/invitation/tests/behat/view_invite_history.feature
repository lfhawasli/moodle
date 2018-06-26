@ucla @enrol_invitation @CCLE-4476
Feature: Invite history
  In order to manage my invitations I've sent
  As an instructor
  I want to be able to view them all and their acceptance status

  Scenario Outline:
    Given I am in a ucla environment
    And the following ucla "sites" exist:
      | shortname | fullname | type |
      | COURSE1 | Course 1 | <type> |
    And the following "users" exist:
      | username | email |
      | sender | send@asd.com |
      | receiver1 | receiv1@asd.com |
      | receiver2 | receiv2@asd.com |
      | receiver3 | receiv3@asd.com |
    And the following ucla "course enrolments" exist:
      | user | course | role |
      | sender | COURSE1 | <role> |
    And the following ucla "roles" exist:
      | role |
      | <inviterole1> |
      | <inviterole2> |
    When I log in as "sender"
    And I am on "Course 1" course homepage
    And I follow "Admin panel"
    And I follow "Invite users"
    And I set the following fields to these values:
      | <rolename1> | 1 |
      | Email address | receiv1@asd.com, receiv2@asd.com |
    And I press "Invite users"
    And I press "Send another invite"
    And I set the following fields to these values:
      | <rolename2> | 1 |
      | Email address | receiv3@asd.com |
    And I press "Invite users"
    And I log out
    And I log in as "receiver1"
    And I follow the link in the last invitation sent to "receiver1" for site "Course 1"
    And I press "Accept invitation"
    And I log out
    And I log in as "receiver3"
    And I follow the link in the last invitation sent to "receiver3" for site "Course 1"
    And I press "Accept invitation"
    And I log out
    And I log in as "sender"
    And I am on "Course 1" course homepage
    And I follow "Admin panel"
    And I follow "Invite users"
    And I follow "Invite history"
    And I should see <rolestring1> in the "receiv1@asd.com" "table_row"
    And I should see "Accepted" in the "receiv1@asd.com" "table_row"
    And I should see <rolestring1> in the "receiv2@asd.com" "table_row"
    And I should see "Active" in the "receiv2@asd.com" "table_row"
    And I should see "expires in 14 days" in the "receiv2@asd.com" "table_row"
    And I should see <rolestring2> in the "receiv3@asd.com" "table_row"
    And I should see "Accepted" in the "receiv3@asd.com" "table_row"
    
    Examples:
      | type | role | inviterole1 | inviterole2 | rolename1 | rolename2 | rolestring1 | rolestring2 |
      | non_instruction | projectlead | projectparticipant | projectviewer | Project Participant | Project Viewer | "Project Participant" | "Project Viewer" |