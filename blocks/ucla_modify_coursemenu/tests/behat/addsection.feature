@ucla @block_ucla_modify_coursemenu
Feature: Adding a section
  As a teacher
  I need to be able to add new sections
  So that I can organize my context

  Background: UCLA environment and srs site exists
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

  @javascript
  Scenario: Log in and modify section
    And I log in as ucla "teacher1"
    And I browse to site "C1"
    And I turn editing mode on
    And I follow "Modify sections"
    And I wait "2" seconds
    And I press "Add new section"
    Then I should see "New"
    And I press "Save changes"
    Then I should see "The sections have been successfully updated."
