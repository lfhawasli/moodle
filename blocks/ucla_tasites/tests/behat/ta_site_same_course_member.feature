@ucla @block_ucla_tasites @same_member @javascript
Feature: Same Member
    In order to provide content to my students
    As a TA
    I want have the same course members in my TA site as in the course site

Scenario: Check participants on TA Site
   Given I am in a ucla environment
   And the following "users" exist:
        | username | firstname | lastname | email        | idnumber  |
        | ta1      | TA        | 1        | ta1@asd.com  | 123456789 |
        | student1 | Student   | 1        | stu1@asd.com | 123456788 |
        | teacher1 | Teacher   | 1        | tea1@asd.com | 123456787 |
   And the following ucla "sites" exist:
        | fullname      | shortname | type | term | srs       |
        | Test course 1 | C1        | srs  | 16S  | 111222000 |
    # Need ta and ta_admin roles for TA sites to work.
    And the following ucla "roles" exist:
        | role     |
        | ta_admin |
   And the following ucla "enrollments" exist:
      | user     | course | role              |
      | teacher1 | C1     | editinginstructor |
      | ta1      | C1     | ta                |
      | student1 | C1     | student           |
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
    And I should see "Successfully created TA site"
    And I follow the "Site info" section in the ucla site menu
    And I follow "View site"
    And I follow "Admin panel"
    And I follow "View participants"
    Then I should see "1, Student"
    And I should see "1, Teacher"
    And I should see "1, TA"
