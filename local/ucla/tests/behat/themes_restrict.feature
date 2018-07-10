@ucla @local_ucla @collabsitetheme
Feature: Restrict theme for collab site
  In order to change theme for collaboration site
  As a manager/admin
  I need to go to Appearance in Edit settings and select a theme from Force theme, and I should only see UCLA course theme and UCLA theme

  @javascript
  Scenario: Confirming force theme is restricted
    Given I am in a ucla environment
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | manager1 | Manager | 1 | manager1@asd.com |
   And the following ucla "sites" exist:
      | fullname | shortname | type |
      | Course 1 | C1 | research |
    And the following ucla "enrolments" exist:
      | user | course | role |
      | manager1 | C1 | manager |
    And I log in as "manager1"
    And I am on "Course 1" course homepage
    And I follow "Edit settings"
    And I expand all fieldsets
    Then I should see "UCLA course theme" in the "Force theme" "select"
    Then I should see "UCLA theme" in the "Force theme" "select"
    Then I should not see "Clean" in the "Force theme" "select"
