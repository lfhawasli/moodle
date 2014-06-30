@ucla @local_ucla_syllabus
Feature: Handle manually uploaded "Syllabus" files
    As an instructor
    I want to be able to convert an existing file I uploaded to a syllabus
    So that I can more easily have a syllabus for my course

Background:
    Given I am in a ucla environment
    And the following "users" exists:
        | username | firstname | lastname | email |
        | teacher1 | Teacher | 1 | teacher1@asd.com |
    And the following ucla "sites" exists:
        | fullname | shortname | type |
        | course 1 | C1 | srs |
    And the following ucla "enrollments" exists:
        | user | course | role |
        | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I browse to site "C1"
    And I turn editing mode on
    And I follow the "Show all" section in the ucla site menu

@javascript @testme
Scenario: Converting files called "Syllabus"
    When I add a "File" to section "1" and I fill the form with:
        | Name | Syllabus |
        | Description | Syllabus test file |
    And I upload "lib/tests/fixtures/empty.txt" file to "Select files" filepicker
    And I press "Save and return to course"
    Then I should see "Syllabus"
    And I should see "We found a resource that might be a syllabus called \"Syllabus\". Would you like to make this your official syllabus?"
    When I press "Yes"
    Then I should see "Please select the type of syllabus to convert your existing \"Syllabus\" resource."
    When I follow "Add \"Syllabus\" as a syllabus"
    Then I should see "Please complete the form below to convert your existing \"Syllabus\" resource into your official UCLA syllabus."
    When I press "Save changes"
    Then I should see "Successfully converted manual syllabus."
    When I follow the "Week 1" section in the ucla site menu
    # Converting a manual syllabus should delete the old file.
    Then I should not see "Syllabus" in the "region-main" "region"

