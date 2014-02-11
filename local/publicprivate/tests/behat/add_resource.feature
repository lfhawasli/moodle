@ucla @local_publicprivate
Feature: Adding a resource
    As an instructor
    I want added resources to be private by default
    So that content is not visible to unenrolled users

    Background: UCLA environment and srs site exists
        Given I am in a ucla environment
        And the following "users" exists:
          | username | firstname | lastname | email |
          | teacher1 | Teacher | 1 | teacher1@asd.com |
          | student1 | Student | 1 | student1@asd.com |
          | student2 | Student | 2 | student2@asd.com |
        And the following ucla "sites" exists:
            | fullname | shortname | type |
            | course 1 | C1 | srs |
        And the following ucla "enrollments" exists:
          | user | course | role |
          | teacher1 | C1 | editingteacher |
          | student1 | C1 | student |
        And I log in as ucla "teacher1"
        And I browse to site "C1"
        And I turn editing mode on
        
    @javascript
    Scenario: Adding an activity and a resource and check public/private visibility
              As an enrolled student and as an unerolled user.
        And I follow "Week 1"
        And I add a "Glossary" to section "1" and I fill the form with:
          | Name | Test glossary name |
          | Description | Test glossary description |
        Then "Test glossary name" activity should be private
        And I add a "File" to section "1"
        And I fill the moodle form with:
          | Name | Test file name |
          | Description | Test file description |
        And I upload "blocks/ucla_easyupload/tests/fixtures/test.txt" file to "Select files" filepicker
        And I press "Save and return to course"
        Then "Test file name" activity should be private
        # Log out and check if student or guest can see activity and resource
        Given I log out
        And I log in as ucla "student1"
        And I browse to site "C1"
        And I follow "Week 1"
        Then "Test glossary name" activity should be public
        And "Test file name" activity should be public
        And I log out
        # Check that other users cannot see private material
        Given I log in as ucla "student2"
        And I browse to site "C1"
        And I follow "Week 1"
        Then I should not see "Test glossary name"
        And I should not see "Test file name"
        And I log out
        # Make material private
        Given I log in as ucla "teacher1"
        And I browse to site "C1"
        And I follow "Week 1"
        And I turn editing mode on
        And I make "Test file name" public
        And I make "Test glossary name" public
        And I log out
        And I log in as ucla "student2"
        And I browse to site "C1"
        And I follow "Week 1"
        Then "Test glossary name" activity should be public
        And "Test file name" activity should be public
        