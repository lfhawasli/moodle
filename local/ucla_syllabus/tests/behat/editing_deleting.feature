@ucla @local_ucla_syllabus @javascript
Feature: Editing and deleting a syllabus
    As an instructor
    I want to be able to edit and delete a syllabus
    So that I can manage my syllabus information

Background:
    Given I am in a ucla environment
    And the following "users" exist:
        | username | firstname | lastname | email |
        | teacher1 | Teacher | 1 | teacher1@asd.com |
        | student1 | Student | 1 | student1@asd.com |
    And the following ucla "sites" exist:
        | fullname | shortname | type |
        | Course 1 | C1 | srs |
    And the following ucla "enrollments" exist:
        | user | course | role |
        | teacher1 | C1 | editingteacher |
        | student1 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I follow the "Syllabus (empty)" section in the ucla site menu
    And I follow "Add syllabus"
    And I click on "URL" "radio"
    And I set the field "syllabus_url" to "http://ucla.edu"
    And I press "Save changes"
    And I press "Continue"

Scenario: Editing syllabus information
    Given I should see "http://ucla.edu"
    And I should see "Syllabus"
    When I follow "Edit"
    And I set the field "syllabus_url" to "http://www.uclabruins.com/"
    And I set the field "Display name" to "Course outline"
    And I press "Save changes"
    And I press "Continue"
    Then I should see "http://www.uclabruins.com/"
    And I should see "Course outline"

Scenario: Deleting syllabus
    When I delete a public syllabus
    Then I should see "Successfully deleted syllabus"
