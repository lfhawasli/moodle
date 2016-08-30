@ucla @block_ucla_office_hours
Feature: Make sure office hours block properly reflects renamed roles
  As a manager
  I need to make sure that the office hours block properly reflects
  renamed roles

  Scenario: Check to see that Instructor role is renamed
    Given I am in a ucla environment
    And the following "users" exist:
      | username | firstname | lastname | email |
      | manager  | Mana      | Ger      | manager@asd.com |
      | instructor1 | Instructor | 1 | instructor1@asd.com |
      | teachinginstructor1 | Teachinginstructor | 1 | ti1@asd.com |
      | itinstructor1 | ITInstructor | 1 | iti1@asd.com |
      | superta1 | SuperTA | 1 | superta1@asd.com |
      | taadmin1 | TAAdmin | 1 | taadmin1@asd.com |
      | teachingassistant1 | TA | 1 | ta1@asd.com |
      | tastudent1 | TAStudent | 1 | tastudent1@asd.com |
    And the following ucla "sites" exist:
        | fullname | shortname | type |
        | course 1 | C1 | srs |
    And the following ucla "enrollments" exist:
      | user | course | role |
      | manager | C1 | manager |
      | instructor1 | C1 | editinginstructor |
      | teachinginstructor1 | C1 | ta_instructor |
      | itinstructor1 | C1 | editinginstructor |
      | itinstructor1 | C1 | ta_instructor |
      | superta1 | C1 | ta_instructor |
      | superta1 | C1 | ta_admin |
      | superta1 | C1 | ta |
      | taadmin1 | C1 | ta_admin |
      | taadmin1 | C1 | ta |
      | teachingassistant1 | C1 | ta |
      | tastudent1 | C1 | ta |
      | tastudent1 | C1 | student |
    # Only managers allowed to rename roles.
    And I log in as "manager"
    And I browse to site "C1"
    And I turn editing mode on
    And I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    When I set the following fields to these values:
      | Your word for 'Instructor ' | Professor |
      | Your word for 'TA Instructor' | Graduate Professor |
      | Your word for 'Teaching Assistant (admin)' | TA Admin |
      | Your word for 'Teaching Assistant' | Teacher Fellows |
    And I press "Save and display"
    And I browse to site "C1"
    Then I should see "Professor"
    And I should see "Teacher Fellows"
    And I should not see "Graduate Professor"
    And I should not see "TA Admin"
    And I should see "1, Instructor" in the ".editinginstructor" "css_element"
    And I should see "1, Teachinginstructor" in the ".editinginstructor" "css_element"
    And I should see "1, ITInstructor" in the ".editinginstructor" "css_element"
    And I should see "1, SuperTA" in the ".editinginstructor" "css_element"
    And I should see "1, TAAdmin" in the ".ta" "css_element"
    And I should see "1, TA" in the ".ta" "css_element"
    And I should see "1, TAStudent" in the ".ta" "css_element"
