@ucla @block_ucla_control_panel
Feature: Adding a resource via the Control Panel
    As an instructor
    I want to add a resource via the Control Panel
    So that I can add a resource easily

   Background:
      Given I am in a ucla environment
      And the following "users" exists:
         | username | firstname | lastname | email |
         | teacher1 | Teacher | 1 | teacher1@asd.com |
      And the following ucla "sites" exists:
         | fullname | shortname | type |
         | course 1 | C1 | srs |
      And the following ucla "enrollments" exists:
         | user | course | role |
         | teacher1 | C1 | editingteacher |

   Scenario Outline: Adding resources
      Given I log in as ucla "teacher1"
      And I browse to site "C1"
      When I press "Control Panel"
      And I follow "Add a resource"
      And I select "<Resource>" from "Resource"
      And I press "Save changes"
      Then I should see "Adding a new <Resource>"

      Examples:
         | Resource |
         | Book |
         | File |
         | Folder |
         | IMS content package |
         | Label |
         | Page |
         | URL |