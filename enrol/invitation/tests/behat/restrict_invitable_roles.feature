@ucla @enrol_invitation
Feature: Restrict invitable roles depending on site type
  In order to limit what roles I can invite
  As a inviter
  I need to see only the roles that make sense for the site type I am in

  @javascript
  Scenario: Make sure Registrar courses show proper roles
    Given I am in a ucla environment
    And the following "users" exists:
      | username | firstname | lastname | email |
      | instructor | Instructor | 1 | instructor@asd.com |
    And the following ucla "sites" exists:
        | fullname | shortname | type |
        | Course1 | C1 | srs |
    And the following ucla "enrollments" exists:
      | user | course | role |
      | instructor | C1 | editinginstructor |
    And I log in as "instructor"
    And I browse to site "C1"
    And I expand "Users" node
    And I follow "Enrolled users"
    When I press "Invite user"
    Then I should see "Instructional Assistant"
    And I should see "Editor"
    And I should see "Grader"
    And I should see "Participant"
    And I should see "Visitor"
