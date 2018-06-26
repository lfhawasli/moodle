@ucla @enrol_invitation @CCLE-4476
Feature: Custom email invite message
  In order to provide information to invitees of my course
  As an instructor or project lead
  I want to include a custom message

  Scenario Outline: Inviter can customise the message
    Given I am in a ucla environment
    And the following ucla "sites" exist:
      | shortname | fullname | type |
      | COURSE1 | Course 1 | <coursetype> |
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
      | role_group[roleid] | <roleid> |
      | Email address | receiv@asd.com |
      | Message | This is a custom message. |
    And I press "Invite users"
    Then the last invite sent to "receiver" should contain "This is a custom message."

    Examples:
      | coursetype | role | inviterole | roleid |
      | class | editingteacher | editor | 9 |
      | non_instruction | projectlead | projectparticipant | 9 |