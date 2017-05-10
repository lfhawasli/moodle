@ucla @local_publicprivate @CCLE-5113
Feature: Toggle public/private with Edit dropdown
    As an instructor
    I want to make activities and resources public or private through the Edit dropdown

    Background: UCLA environment and srs site exists
        Given I am in a ucla environment
        And the following "users" exist:
          | username | firstname | lastname | email |
          | teacher1 | Teacher | 1 | teacher1@asd.com |
          | student1 | Student | 1 | student1@asd.com |
          | student2 | Student | 2 | student2@asd.com |
        And the following ucla "sites" exist:
          | fullname | shortname | type |
          | course 1 | C1 | srs |
        And the following ucla "enrollments" exist:
          | user | course | role |
          | teacher1 | C1 | editingteacher |
          | student1 | C1 | student |
        And I log in as "teacher1"
        And I browse to site "C1"
        And I turn editing mode on

    @javascript
    Scenario: Adding an activity and a resource and toggling and checking
              public/private visibility
        And I follow "Week 1"
        And I add a "Assignment" to section "1" and I fill the form with:
        | Assignment name | Test assignment |
        | Description | Test assignment description |
        And I make "Test assignment" public
        And I reload the page
        Then "Test assignment" activity should be public
        And I add a "Label" to section "1" and I fill the form with:
        | Label text | Test label |
        And I make "Test label" public
        And I reload the page
        Then "Test label" activity should be public
        # Log out and check if student or guest can see activity and resource
        Given I log out
        And I log in as "student1"
        And I browse to site "C1"
        And I follow "Week 1"
        Then "Test assignment" activity should be public
        And "Test label" activity should be public
        And I log out
        # Check that other users or public can also see public material
        Given I log in as "student2"
        And I browse to site "C1"
        And I follow "Week 1"
        Then I should see "Test assignment"
        And I should see "Test label"
        And I log out
        And I browse to site "C1"
        And I follow "Week 1"
        Then I should see "Test assignment"
        And I should see "Test label"
        # Toggling to private visibility with Edit dropdown
        And I log in as "teacher1"
        And I browse to site "C1"
        And I follow "Week 1"
        And I turn editing mode on
        And I make "Test assignment" private
        And I reload the page
        Then "Test assignment" activity should be private
        And I make "Test label" private
        And I reload the page
        Then "Test label" activity should be private
        # Log out and check if student or guest can see activity and resource
        Given I log out
        And I log in as "student1"
        And I browse to site "C1"
        And I follow "Week 1"
        Then I should see "Test assignment"
        And I should see "Test label"
        And I log out
        # Check that other users or public cannot see private material
        Given I log in as "student2"
        And I browse to site "C1"
        And I follow "Week 1"
        Then I should not see "Test assignment"
        And I should not see "Test label"
        And I log out
        And I browse to site "C1"
        And I follow "Week 1"
        Then I should not see "Test assignment"
        And I should not see "Test label"