@ucla @enrol_invitation @CCLE-4476
Feature: Different roles for hidden sites
   In order to make sure different roles allowed for an invite work properly for hidden sites
   As an instructor
   I want to verify when the site is hidden only the roles that can access hidden sites should be able to be invited

   Background:
      Given I am in a ucla environment
      And I log in as "admin"
      And I set the following administration settings values:
         | enabletempparticipant | true |
      And I log out

   Scenario: Roles available for invite for hidden SRS site
      Given the following "users" exist:
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
         | instructional_assistant |
         | grader |
         | editor |
         | participant |
         | visitor |
         | tempparticipant |
      And I log in as "teacher1"
      And I browse to site "C1"
      When I click on "Edit settings" "link" in the "Administration" "block"
      And I set the following fields to these values:
         | Visible | Hide |
      And I press "Save and display"
      And I follow "Admin panel"
      And I follow "Invite users"
      Then I should see "Instructional Assistant"
      And I should see "Editor"
      And I should see "Temporary Participant"
      And I should not see "Grader"
      And I should not see "Visitor"
      And I should not see "Participant Participant: can participate"

   Scenario: Roles available for invite for hidden instructional site
      Given the following "users" exist:
         | username | firstname | lastname | email |
         | teacher1 | Teacher | 1 | teacher1@asd.com |
      And the following ucla "sites" exist:
         | fullname | shortname | type |
         | course 1 | C1 | instruction |
      And the following ucla "enrollments" exist:
         | user | course | role |
         | teacher1 | C1 | editingteacher |
      And the following ucla "roles" exist:
         | role |
         | instructional_assistant |
         | grader |
         | editor |
         | participant |
         | visitor |
         | tempparticipant |
      And I log in as "teacher1"
      And I browse to site "C1"
      When I click on "Edit settings" "link" in the "Administration" "block"
      And I set the following fields to these values:
         | Visible | Hide |
      And I press "Save and display"
      And I follow "Admin panel"
      And I follow "Invite users"
      Then I should see "Instructional Assistant"
      And I should see "Editor"
      And I should see "Temporary Participant"
      And I should not see "Grader"
      And I should not see "Visitor"
      And I should not see "Participant: can participate"

   Scenario: Roles available for invite for hidden research site
      Given the following "users" exist:
         | username | firstname | lastname | email |
         | teacher1 | Teacher | 1 | teacher1@asd.com |
      And the following ucla "sites" exist:
         | fullname | shortname | type |
         | course 1 | C1 | research |
      And the following ucla "enrollments" exist:
         | user | course | role |
         | teacher1 | C1 | editingteacher |
      And the following ucla "roles" exist:
         | role |
         | projectlead |
         | projectcontributor |
         | projectparticipant |
         | projectviewer |
         | tempparticipant |
      And I log in as "teacher1"
      And I browse to site "C1"
      When I click on "Edit settings" "link" in the "Administration" "block"
      And I set the following fields to these values:
         | Visible | Hide |
      And I press "Save and display"
      And I follow "Admin panel"
      And I follow "Invite users"
      Then I should see "Project Lead"
      And I should see "Project Contributor"
      And I should see "Temporary Participant"
      And I should not see "Project Participant"
      And I should not see "Project Viewer"

   @javascript
   Scenario: Roles available for invite for hidden TA site
      Given the following "users" exist:
         | username | firstname | lastname | email | idnumber |
         | student1 | Student | 1 | student1@asd.com | 123456789 |
         | teacher1 | Teacher | 1 | teacher1@asd.com | 987654321 |
         | ta2 | TA | 2 | ta2@asd.com | 111222333 |
         | ta1 | TA | 1 | ta1@asd.com | 999888777 |
      And the following ucla "sites" exist:
         | fullname | shortname | type |
         | Test course 1 | C1 | srs |
      And the following ucla "enrollments" exist:
         | user | course | role |
         | teacher1 | C1 | editingteacher |
         | ta2 | C1 | ta_admin |
         | ta1 | C1 | ta |
         | student1 | C1 | student |
      And the following ucla "roles" exist:
         | role |
         | instructional_assistant |
         | grader |
         | editor |
         | participant |
         | visitor |
         | tempparticipant |
      And I log in as "ta2"
      And I follow "Test course 1"
      And I follow "Admin panel"
      And I follow "TA sites"
      And I click on "#id_confirmation" "css_element"
      And I press "id_submitbutton"
      And I should see "Successfully created TA site"
      When I click on "Edit settings" "link" in the "Administration" "block"
      And I set the following fields to these values:
         | Visible | Hide |
      And I press "Save and display"
      And I follow "Admin panel"
      And I follow "Invite users"
      Then I should see "Instructional Assistant"
      And I should see "Editor"
      And I should see "Temporary Participant"
      And I should not see "Grader"
      And I should not see "Visitor"
      And I should not see "Participant Participant: can participate"