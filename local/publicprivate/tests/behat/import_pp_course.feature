@ucla @local_publicprivate
Feature: Import a course with public/private material
  In order to move and copy contents with public/private settings between courses
  As a teacher or admin
  I need to import a course contents into another course

  Background:
    Given I am in a ucla environment
    And the following ucla "sites" exist:
      | fullname | shortname | type |
      | Source   | source | instruction |
      | Target   | target | instruction |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
      | student1 | Student | 1 | student1@asd.com |
    And the following ucla "enrollments" exist:
      | user | course | role |
      | teacher1 | source | editinginstructor |
      | teacher1 | target | editinginstructor |
    And the following ucla "activities" exist:
      | activity | course | idnumber | name | intro | section | private | visible |
      | assign | source | assignprivate | Private assign | Private assignment | 0 | 1 | 1 |
      | assign | source | assignpublic | Public assign | Public assignment | 0 | 0 | 1 |
      | data | source | dataprivate | Private database | Private database | 1 | 1 | 1 |
      | data | source | datapublic| Public database | Public database | 1 | 0 | 1 |
      | page | source | pageprivate | Private page | Private page | 2 | 1 | 1 |
      | page | source | pagepublic | Public page | Public page | 2 | 0 | 1 |
      | resource | source | resourceprivate | Private resource | Private resource | 3 | 1 | 1 |
      | resource | source | resourcepublic | Public resource | Public resource | 3 | 0 | 1 |

  @javascript
  Scenario: Importing a course
    When I log in as "teacher1"
    And I import "Source" course into "Target" course using this options:
    And I follow "Target"
    And I turn editing mode on
    # Check visibility and privacy
    Then I should see "Private assign"
    And I should see "Public assign"
    And "Private assign" activity should be private
    And "Public assign" activity should be public
    When I log out
    And I log in as "student1"
    And I browse to site "target"
    And I follow "Week 1"
    Then I should not see "Private database"
    And I should see "Public database"
    And I log out
    And I log in as "teacher1"
    And I browse to site "target"
    And I turn editing mode on
    And I follow "Week 1"
    And "Private database" activity should be private
    And "Public database" activity should be public
    When I follow "Week 2"
    Then "Private page" activity should be private
    And "Public page" activity should be public
    When I follow "Week 3"
    Then "Private resource" activity should be private
    And "Public resource" activity should be public
    # Check course settings
    When I click on "Edit settings" "link" in the "Administration" "block"
    Then the following fields match these values:
      | Public/Private | Enable |