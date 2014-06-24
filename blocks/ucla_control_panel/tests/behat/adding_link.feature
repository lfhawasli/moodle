@ucla @block_ucla_control_panel
Feature: Add a link
    In order to easily add a link to my course
    As an instructor
    I want to add a link using the control panel

   Background:
      Given I am in a ucla environment
      And the following "users" exists:
         | username | firstname | lastname | email |
         | teacher1 | teacher | 1 | teacher1@asd.com |
      And the following ucla "sites" exists:
         | fullname | shortname | type |
         | Course 1 | C1 | srs |
      And the following ucla "enrollments" exists:
         | user | course | role |
         | teacher1 | C1 | editingteacher |

   Scenario: Adding a private link
      Given I log in as ucla "teacher1"
      And I follow "Course 1"
      When I press "Control Panel"
      And I follow "Add a link"
      And I set the field "Enter link URL" to "http://google.com"
      And I set the field "Name" to "Google"
      And I select "Week 1" from "Add to section"
      And I press "Save changes"
      And I press "Return to section"
      Then I should see "Week 1" highlighted in the ucla site menu
      And "Google" activity should be private
      When I log out
      And I follow "Course 1"
      And I follow "Week 1"
      Then I should not see "Google" in the "region-main" "region"

   Scenario: Adding a public link
      Given I log in as ucla "teacher1"
      And I follow "Course 1"
      When I press "Control Panel"
      And I follow "Add a link"
      And I set the field "Enter link URL" to "http://cnet.com"
      And I set the field "Name" to "Cnet"
      And I select "Make public" radio button
      And I select "Week 1" from "Add to section"
      And I press "Save changes"
      And I press "Return to section"
      Then I should see "Week 1" highlighted in the ucla site menu
      Then "Cnet" activity should be public
      When I log out
      And I follow "Course 1"
      And I follow "Week 1"
      Then I should see "Cnet" in the "region-main" "region"
 