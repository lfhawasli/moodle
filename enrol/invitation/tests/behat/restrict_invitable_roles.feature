@ucla @enrol_invitation
Feature: Restrict invitable roles depending on site type
   In order to limit what roles I can invite
   As a inviter
   I want to see only the roles that make sense for the site type I am in
 
   Scenario: Make sure Registrar courses show proper roles
      Given I am in a ucla environment
      And the following "users" exist:
         | username | firstname | lastname | email |
         | instructor | Instructor | 1 | instructor@asd.com |
      And the following ucla "sites" exist:
         | fullname | shortname | type |
         | Course 1 | C1 | srs |
      And the following ucla "enrollments" exist:
         | user | course | role |
         | instructor | C1 | editinginstructor |
      And I log in as "instructor"
      And I follow "Course 1"
      And I expand "Users" node
      And I follow "Enrolled users"
      When I press "Invite user"
      Then I should see "Instructional Assistant"
      And I should see "Editor"
      And I should see "Grader"
      And I should see "Participant"
      And I should see "Visitor"
