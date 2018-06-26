@ucla @enrol_invitation @CCLE-4476
Feature: Invitation form
  In order to invite a user to have a role in my site
  As an instructor or project lead
  I want to be able to see a description of my role options and be alerted if I input an invalid email

  Scenario Outline: See role descriptions
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
    And I am on "Course 1" course homepage
    And I follow "Admin panel"
    And I follow "Invite users"
    Then I should see <roledescrip1>
    And I should see <roledescrip2>

    Examples:
      | coursetype | rolename | role1 | role2 | roledescrip1 | roledescrip2 |
      | class | editingteacher | instructional_assistant | visitor | "Instructional Assistant: can" | "Visitor: can" |
      | non_instruction | projectlead | projectlead | projectviewer | "Project Lead: can" | "Project Viewer: can" |

  Scenario: Error message for invalid email
    Given I am in a ucla environment
    And the following "users" exist:
      | username |
      | testuser |
    And the following ucla "sites" exist:
      | shortname | fullname | type |
      | COURSE1 | Course 1 | class |
    And the following ucla "course enrolments" exist:
      | user | course | role |
      | testuser | COURSE1 | editingteacher |
    And the following ucla "roles" exist:
      | role     |
      | testuser |
      | grader   |
    When I log in as "testuser"
    And I am on "Course 1" course homepage
    And I follow "Admin panel"
    And I follow "Invite users"
    And I set the following fields to these values:
      | Grader | 1 |
      | Email address | testing@asd.com, testing2asd.com |
    And I press "Invite users"
    Then I should see "You must enter a valid email address here"