@ucla @local_ucla_syllabus
Feature: Editing and deleting a syllabus
    As an instructor
    I want to be able to edit and delete a syllabus
    So that I can manage my syllabus information

Background:
    Given I am in a ucla environment
    And the following "users" exists:
        | username | firstname | lastname | email |
        | teacher1 | Teacher | 1 | teacher1@asd.com |
        | student1 | Student | 1 | student1@asd.com |
    And the following ucla "sites" exists:
        | fullname | shortname | type |
        | course 1 | C1 | srs |
    And the following ucla "enrollments" exists:
        | user | course | role |
        | teacher1 | C1 | editingteacher |
        | student1 | C1 | student |
    And I log in as "teacher1"
    And I browse to site "C1"
    And I turn editing mode on
    And I follow the "Syllabus (empty)" section in the ucla site menu
    And I follow "Add syllabus"
    And I fill in "URL" with "http://ucla.edu"
    And I press "Save changes"

Scenario: Editing syllabus information
    Given I should see "http://ucla.edu"
    And I should see "Syllabus"
    When I follow "Edit"
    And I fill in "URL" with "http://www.uclabruins.com/"
    And I fill in "Display name" with "Course outline"
    And I press "Save changes"
    Then I should see "http://www.uclabruins.com/"
    And I should see "Course outline"

Scenario: Deleting syllabus
    When I delete a public syllabus
    Then I should see "Successfully deleted syllabus"
