@ucla @theme_uclasharedcourse
Feature: Category banners
  In order to brand certain collaboration sites
  As an admin
  I need to be able to replace the default CCLE logo

  Background:
    Given I am in a ucla environment
    And the following "categories" exist:
      | name       | category | idnumber |
      | Management | 0        | MG       |
      | Centers    | MG       | center   |
      | Executive Education Program | center | exec |

  Scenario: Site under category "Executive Education Program" WITH "UCLA course theme"
    Given the following ucla "sites" exist:
      | fullname | shortname | category | format | type        |
      | Course 1 | C1        | exec     | ucla   | instruction |
    And I log in as "admin"
    And I follow "Course 1"
    And I should not see image "executive_education_program" in the ".header-logo" "css_element"
    And I follow "Edit settings"
    And I set the following fields to these values:
      | Force theme | UCLA course theme |
    And I press "Save changes"
    Then I should see image "executive_education_program" in the ".header-logo" "css_element"

    Scenario: Site under any other category
      Given the following ucla "sites" exist:
        | fullname | shortname | category | format | type        |
        | Course 1 | C1        | center   | ucla   | instruction |
      And I log in as "admin"
      And I follow "Course 1"
      And I should not see image "executive_education_program" in the ".header-logo" "css_element"
      And I follow "Edit settings"
      And I set the following fields to these values:
        | Force theme | UCLA course theme |
      And I press "Save changes"
      Then I should not see image "executive_education_program" in the ".header-logo" "css_element"