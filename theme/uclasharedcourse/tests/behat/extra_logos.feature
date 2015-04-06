@ucla @theme_uclasharedcourse @javascript
Feature: Extra logos for collaboration sites.
  In order to brand certain collaboration sites
  As an admin
  I need to be able to add additional logos to the page banner.

  Background:
    Given I am in a ucla environment
    And the following "categories" exist:
      | name       | category | idnumber |
      | Management | 0        | MG       |
      | Centers    | MG       | center   |
      | Executive Education Program | center | exec |
    And the following ucla "sites" exist:
      | fullname | shortname | category | format | type        |
      | Course 1 | C1        | exec     | ucla   | private     |
    And the following "users" exist:
      | username | firstname | lastname | email            |
      | student  | Student   | 1        | student1@asd.com |
      | nobody   | Nobody    | 1        | nobody1@asd.com  |
    And the following "course enrolments" exist:
      | user    | course | role |
      | student | C1     | student |
    And I log in as "admin"
    And I follow "Course 1"
    # First need to enable course theme before can upload logos.
    And I follow "Edit settings"
    And I set the following fields to these values:
      | Force theme | UCLA course theme |
    And I press "Save changes"
    And I follow "Edit settings"
    And I expand all fieldsets
    And I upload "pix/moodlelogo.png" file to "Additional header logos" filemanager
    And I press "Save changes"
    And I log out

  Scenario: Extra logos visible to enrolled users
    Given I log in as "student"
    When I follow "Course 1"
    And I should see image "moodlelogo.png" in the ".course-logo-image" "css_element"

  Scenario: Extra logos not visible no non-enrolled users
    Given I log in as "nobody"
    When I follow "Course 1"
    And I should not see image "moodlelogo.png" in the ".course-logo-image" "css_element"