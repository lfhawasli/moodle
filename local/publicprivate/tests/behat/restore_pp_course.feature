@ucla @local_publicprivate @foo
Feature: Restore course backups with public/private content
  In order to continue using my stored course contents with public/private settings
  As a teacher and an admin
  I need to restore them inside other Moodle courses or in new courses

    Background:
        Given I am in a ucla environment
        And the following "courses" exists:
          | fullname | shortname | format | numsections |
          | Source | source | ucla | 10 |
          | Target | target | ucla | 10 |
        And the following ucla "activities" exists:
          | activity | course | idnumber | name | intro | section | private |
          | assign | source | assignprivate | Private assign | Private assignment | 0 | 1 |
          | assign | source | assignpublic | Public assign | Public assignment | 0 | 0 |
          | data | source | dataprivate | Private database | Private database | 1 | 1 |
          | data | source | datapublic| Public database | Public database | 1 | 0 |
          | page | source | pageprivate | Private page | Private page | 2 | 1 |
          | page | source | pagepublic | Public page | Public page | 2 | 0 |
          | resource | source | resourceprivate | Private resource | Private resource | 3 | 1 |
          | resource | source | resourcepublic | Public resource | Public resource | 3 | 0 |
        And I log in as ucla "admin"
        And I follow "Source"
        Then I follow "Site info"
        And I put a breakpoint
        And "Private assign" activity should be private
        And "Public assign" activity should be public
        Then I follow "Week 1"
        And "Private database" activity should be private
        And "Public database" activity should be public
        Then I follow "Week 2"
        And "Private page" activity should be private
        And "Public page" activity should be public
        Then I follow "Week 3"
        And "Private resource" activity should be private
        And "Public resource" activity should be public
        And I backup "Source" course using this options:
              | Filename | source_backup.mbz |

    @javascript
    Scenario: Restoring course
        When I restore "source_backup.mbz" backup into "Target" course using this options:
        Then I follow "Site info"
        And "Private assign" activity should be private
        And "Public assign" activity should be public
        Then I follow "Week 1"
        And "Private database" activity should be private
        And "Public database" activity should be public
        Then I follow "Week 2"
        And "Private page" activity should be private
        And "Public page" activity should be public
        Then I follow "Week 3"
        And "Private resource" activity should be private
        And "Public resource" activity should be public
