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
      And the following ucla "roles" exist:
         | role |
         | instructional_assistant |
         | editor |
         | grader |
         | participant |
         | visitor |
      And I log in as "instructor"
      And I am on "Course 1" course homepage
      And I expand "Users" node
      And I follow "Participants"
      When I press "Invite user"
      Then I should see "Instructional Assistant"
      And I should see "Editor"
      And I should see "Grader"
      And I should see "Participant"
      And I should see "Visitor"

   Scenario: Make sure Temporary participant shows for collaboration sites
      Given I am in a ucla environment
      And I log in as "admin"
      And I set the following administration settings values:
         | enabletempparticipant | 1 |
      And I log out
      And the following "users" exist:
         | username | firstname | lastname | email |
         | projectlead | Lead | Project | instructor@asd.com |
      And the following ucla "sites" exist:
         | fullname | shortname | type |
         | Course 1 | C1 | non_instruction |
      And the following ucla "enrollments" exist:
         | user | course | role |
         | projectlead | C1 | projectlead |
      And the following ucla "roles" exist:
         | role |
         | projectlead |
         | projectcontributor |
         | projectparticipant |
         | projectviewer |
         | tempparticipant |
      And I log in as "projectlead"
      And I am on "Course 1" course homepage
      And I expand "Users" node
      And I follow "Participants"
      When I press "Invite user"
      Then I should see "Project Lead"
      And I should see "Project Contributor"
      And I should see "Project Participant"
      And I should see "Project Viewer"
      And I should see "Temporary Participant"