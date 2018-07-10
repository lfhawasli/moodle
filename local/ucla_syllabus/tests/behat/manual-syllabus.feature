@ucla @local_ucla_syllabus @CCLE-3684
Feature: Handle manually uploaded "Syllabus" files
    As an instructor
    I want to be able to convert an existing file I uploaded to a syllabus
    So that I can more easily have a syllabus for my course

Background:
    Given I am in a ucla environment
    And the following "users" exist:
        | username | firstname | lastname | email |
        | teacher1 | Teacher | 1 | teacher1@asd.com |
    And the following ucla "sites" exist:
        | fullname | shortname | type |
        | Course 1 | C1 | srs |
    And the following ucla "enrollments" exist:
        | user | course | role |
        | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on

@javascript
Scenario: Converting files/URLs called "Syllabus"
    When I follow the "Show all" section in the ucla site menu
    And I add a "File" to section "1"
    And I set the following fields to these values:
        | Name | Syllabus |
        | Description | Syllabus test |
    And I upload "lib/tests/fixtures/empty.txt" file to "Select files" filemanager
    And I press "Save and return to course"
    Then I should see "Syllabus"
    And I should see "We found a resource that might be a syllabus called \"Syllabus\". Would you like to make this your official syllabus?"
    And I press "Ask me later"
    When I press "Yes"
    Then I should see "Please select the type of syllabus to convert your existing \"Syllabus\" resource."
    When I follow "Add \"Syllabus\" as a syllabus"
    Then I should see "Please complete the form below to convert your existing \"Syllabus\" resource into your official UCLA syllabus."
    When I press "Save changes"
    Then I should see "Successfully converted manual syllabus."
    # Converting a manual syllabus should delete the old file.
    When I follow the "Week 1" section in the ucla site menu
    Then I should not see "Syllabus" in the "region-main" "region"
    # Converting a manual syllabus should be logged.
    When I log out
    And I log in as "admin"
    And I navigate to "Logs" node in "Site administration > Reports"
    And I set the following fields to these values:
        | Select a course | Course 1 |
        | Select a user | 1, Teacher |
    And I press "Get these logs"
    Then I should see "Converted manual syllabus"

@javascript
Scenario: Converting files/URLs called "Syllabus"
    When I follow the "Show all" section in the ucla site menu
    And I add a "URL" to section "1"
    And I set the following fields to these values:
        | Name | Syllabus |
        | Description | Syllabus test |
    And I set the field "External URL" to "https://www.google.com"
    And I press "Save and return to course"
    Then I should see "Syllabus"
    And I should see "We found a url that might be a syllabus called \"Syllabus\". Would you like to make this your official syllabus?"
    When I press "Yes"
    Then I should see "Please select the type of syllabus to convert your existing \"Syllabus\" url."
    When I follow "Add \"Syllabus\" as a syllabus"
    Then I should see "Please complete the form below to convert your existing \"Syllabus\" url into your official UCLA syllabus."
    When I press "Save changes"
    Then I should see "Successfully converted manual syllabus."
    When I follow the "Week 1" section in the ucla site menu
    Then I should not see "Syllabus" in the "region-main" "region"
    When I log out
    And I log in as "admin"
    And I navigate to "Logs" node in "Site administration > Reports"
    And I set the following fields to these values:
        | Select a course | Course 1 |
        | Select a user | 1, Teacher |
    And I press "Get these logs"
    Then I should see "Converted manual syllabus"

@javascript
Scenario Outline: Adding file/URL when syllabus already exists
    Given I add a new public syllabus
    When I follow the "Show all" section in the ucla site menu
    And I add a "<resourcename>" to section "1"
    And I set the following fields to these values:
        | Name | Syllabus |
        | Description | Syllabus test |
    And <secondarystep>
    And I press "Save and return to course"
    Then I should not see "We found a <resource> that might be a syllabus called \"Syllabus\". Would you like to make this your official syllabus?"
    Examples:
        | resource | resourcename | secondarystep |
        | resource | File         | I upload "lib/tests/fixtures/empty.txt" file to "Select files" filemanager |
        | url      | URL          | I set the field "External URL" to "https://www.google.com" |
