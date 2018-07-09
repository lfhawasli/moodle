@ucla @block_ucla_tasites @build_ta_site @javascript
Feature: Build TA Site
    In order to have a site in which I can manage
    As a TA
    I want to be able to create a TA site

Background:
    Given I am in a ucla environment
    And the following "users" exist:
        | username | firstname | lastname | email       | idnumber  |
        | ta1      | TA        | 1        | ta1@asd.com | 123456789 |
        | ta2      | TA        | 2        | ta2@asd.com | 987654321 |
    And the following ucla "sites" exist:
        | fullname      | shortname | type | term | srs       |
        | Test course 1 | C1        | srs  | 16S  | 111222000 |
   And the following ucla "enrollments" exist:
        | user | course | role     |
        | ta1  | C1     | ta       |
        | ta2  | C1     | ta_admin |
    # Create TA site for course with no section.
    And the following TA mapping exist:
        | parentsrs | term | uid       |
        | 111222000 | 16S  | 123456789 |
        | 111222000 | 16S  | 987654321 |

Scenario: Build TA site as TA
    Given I log in as "ta1"
    And I follow "Test course 1"
    And I follow "Admin panel"
    When I follow "TA sites"
    And I press "Create TA site"
    And I click on "#id_confirmation" "css_element"
    And I press "id_submitbutton"
    Then I should see "Successfully created TA site"

Scenario: Build TA site as TA Admin   
    Given I log in as "ta2"
    And I follow "Test course 1"
    And I follow "Admin panel"
    When I follow "TA sites"
    And I click on "#id_confirmation" "css_element"
    And I press "id_submitbutton"
    Then I should see "Successfully created TA site"
