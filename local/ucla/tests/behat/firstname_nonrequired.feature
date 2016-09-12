@ucla @local_ucla @core_edit @CCLE-4437
Feature: First name is non-required
  In order to see that first name is not required
  As any user, possibly without a first name when created,
  I need to go to "Edit profile", set "First name" empty and see if it lets me update my profile

  @javascript
  Scenario: Confirming that first name is not required
    Given I am in a ucla environment
    And the following "users" exist:
      | username | lastname | firstname | email |
      | manager1 | Lastname || manager1@asd.com |
    And the following ucla "sites" exist:
      | fullname | shortname | type |
      | Course 1 | C1 | research |
    And the following ucla "enrolments" exist:
      | user | course | role |
      | manager1 | C1 | manager |
    And I log in as "manager1"
    And I follow "Profile" in the user menu
    And I follow "Edit profile"
    When I set the field "First name" to "Firstname"
    And I press "Update profile"
    Then I should see "Firstname"
    And I follow "Edit profile"
    When I set the field "First name" to ""
    And I press "Update profile"
    # There is no success message when updating a profile.
    Then I should see "Last access"
    And I should not see "Firstname"