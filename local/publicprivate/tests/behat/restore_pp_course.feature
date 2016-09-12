@ucla @local_publicprivate
Feature: Restore course backups with public/private content
  In order to continue using my stored course contents with public/private settings
  As a teacher and an admin
  I need to restore them inside other Moodle courses or in new courses

    Background:
        Given I am in a ucla environment
        And the following ucla "sites" exist:
          | fullname | shortname | type |
          | Source | source | instruction |
          | Target | target | instruction |
        And the following "users" exist:
          | username | firstname | lastname | email |
          | student1 | Student | 1 | student1@asd.com |
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
        And I log in as "admin"
        And I backup "Source" course using this options:
              | Confirmation | Filename | source_backup.mbz |

    @javascript
    Scenario Outline: Restoring courses
        When I restore "source_backup.mbz" backup into <Target> course using this options:
        And I follow "Site info"
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
        And I log in as "admin"
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
        When I click on "Edit settings" "link" in the "Administration" "block"
        Then the following fields match these values:
          | Public/Private | Enable |

        Examples:
          | Target |
          | "Target" |
          | a new |