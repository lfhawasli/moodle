@ucla @enrol_invitation @CCLE-5528
Feature: Add customizable From field
  In order to have a specific class email or email on the behalf of someone else
  As an invite sender
  I want to be able to set the From field for the invitation

  Scenario:
    Given I am in a ucla environment
    And the following ucla "sites" exist:
      | shortname | fullname | type |
      | COURSE1 | Course 1 | class |
    And the following "users" exist:
      | username | email |
      | sender | send@asd.com |
    And the following ucla "course enrolments" exist:
      | user | course | role |
      | sender | COURSE1 | editingteacher |
    And the following ucla "roles" exist:
      | role |
      | editor |
    When I log in as "sender"
    And I am on "Course 1" course homepage
    And I follow "Admin panel"
    And I follow "Invite users"
    And I set the following fields to these values:
      | From | differentemail@asd.com |
      | Editor | 1 |
      | Email address | receiv1@asd.com |
    And I press "Invite users"
    And I follow "Invite history"
    And I should see "differentemail@asd.com" in the "receiv1@asd.com" "table_row"
    # Make sure that the from email address gets passed when resending invite.
    When I follow "Revoke invite"
    And I follow "Resend invite"
    And I set the following fields to these values:
      | Email address | receiv2@asd.com |
    And I press "Invite users"
    And I follow "Invite history"
    And I should see "differentemail@asd.com" in the "receiv2@asd.com" "table_row"
