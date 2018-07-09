@ucla @block_ucla_tasites @tasite_admin @javascript
Feature: TA admin in a TA site
    In order to manage a TA site
    As a TA
    I want to be a TA admin in the TA site I create

Scenario: Check if TA is TA (admin) in his TA site
    Given I am in a ucla environment
    And the following "users" exist:
        | username | firstname | lastname | email       | idnumber  |
        | ta1      | TA        | 1        | ta1@asd.com | 123456789 |
    And the following ucla "sites" exist:
        | fullname      | shortname | type | term | srs       |
        | Test course 1 | C1        | srs  | 16S  | 111222000 |
    # Need ta and ta_admin roles for TA sites to work.
    And the following ucla "roles" exist:
        | role     |
        | ta_admin |
    And the following ucla "enrollments" exist:
        | user | course | role |
        | ta1  | C1     | ta   |
    # Create TA site for course with no section.
    And the following TA mapping exist:
        | parentsrs | term | uid       |
        | 111222000 | 16S  | 123456789 |
    And I log in as "ta1"
    And I follow "Test course 1"
    And I follow "Admin panel"
    When I follow "TA sites"
    And I click on "#id_confirmation" "css_element"    
    And I press "id_submitbutton"
    Then I should see "Successfully created TA site"
    And I follow the "Site info" section in the ucla site menu
    And I follow "View site"
    And I expand "Users" node
    And I follow "Participants"
    Then I should see "Teaching Assistant (admin)"
