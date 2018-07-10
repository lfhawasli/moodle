@ucla @enrol_invitation @CCLE-4476
Feature: Temporary participant role invitation
   In order to verify temporary participant role invitation working properly
   As an instructor
   I want to be able to send out invitation for temporary participant role and verify the number of days it will expire

   Scenario: Invitation for temporary participant role
      Given I am in a ucla environment
      And I log in as "admin"
      And I set the following administration settings values:
         | enabletempparticipant | true |
      And I log out
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
         | tempparticipant |
      And I log in as "teacher1"
      And I browse to site "C1"
      And I follow "Admin panel"
      And I follow "Invite users"
      When I set the following fields to these values:
         | role_group[roleid] | 9 |
         | daysexpire | 3 |
         | Email address | s1@asd.com |
         | Subject | Site invitation to course 1 |
      And I press "Invite users"
      And I should see "Invitation successfully sent"
      And I follow "Invite history"
      Then the following should exist in the "generaltable" table:
         | Role |
         | Temporary Participant |
      And I should see "expires in 14 days" in the "s1@asd.com" "table_row"
