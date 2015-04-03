@ucla @enrol_invitation @CCLE-4476
Feature: Help text for different roles
  In order to invite a user to have a role in my site
  As an instructor or project lead
  I want to be able to see a description of my role options

  Scenario Outline: As an instructor of a course
    Given I am in a ucla environment
    And the following "users" exist:
      | username |
      | testuser |
    And the following ucla "sites" exist:
      | shortname | fullname | type |
      | COURSE1 | Course 1 | <coursetype> |
    And the following ucla "course enrolments" exist:
      | user | course | role |
      | testuser | COURSE1 | <rolename> |
    And the following ucla "roles" exist:
      | role |
      | <role1> |
      | <role2> |

    When I log in as "testuser"
    And I follow "Course 1"
    And I press "Control Panel"
    And I follow "Invite users"
    Then I should see <roledescrip1>
    And I should see <roledescrip2>

    Examples:
      | coursetype | rolename | role1 | role2 | roledescrip1 | roledescrip2 |
      | class | editingteacher | instructional_assistant | visitor | "Instructional Assistant: can" | "Visitor: can" |
      | non_instruction | projectlead | projectlead | projectviewer | "Project Lead: can" | "Project Viewer: can" |