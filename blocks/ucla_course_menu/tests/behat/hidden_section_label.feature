@ucla @block_ucla_course_menu
Feature: Hidden section label
  As a teacher
  I need to see the hidden label on sections
  So that I know which sections are hidden

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
  Scenario: Verify visibility of 'hidden' label
    And I log in as "teacher1"
    And I browse to site "C1"
    And I turn editing mode on
    And I follow the "Week 3" section in the ucla site menu
    And I hide section "3"
    And I follow the "Week 2" section in the ucla site menu
    And I add a "Forum" to section "2" and I fill the form with:
      | Forum name | Test forum name |
      | Description | Test forum description |
    And I should see "Test forum name"
    And I hide section "2"
    And I reload the page
    # A section with content should contain the 'hidden' label
    Then the "Week 2" section in the ucla site menu is hidden
    # A section without content should contain the 'hidden' label
    And the "Week 3" section in the ucla site menu is hidden
    # A section that is not hidden, should NOT contain the 'hidden' label
    And the "Week 5" section in the ucla site menu is visible
