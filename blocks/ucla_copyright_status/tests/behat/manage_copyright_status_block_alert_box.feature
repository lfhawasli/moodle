@ucla @block_ucla_copyright_status
Feature: Course display alert for course content that has no copyright status yet.
    As an instructor
    when I go to a course or when I upload a file to the course, it will alert me if I have not assign copyright status to the file(s).

Background:
    Given I am in a ucla environment
    And the following "users" exists:
    | username | firstname | lastname | email |
    | teacher1 | Teacher | 1 | teacher1@abc.com |
    And the following ucla "sites" exists:
    | fullname | shortname | type | numsections |
    | course 1 | C1 | srs | 3 |
    And the following ucla "enrollments" exists:
    | user | course | role |
    | teacher1 | C1 | editingteacher |
    Given I log in as ucla "teacher1"
    And I browse to site "C1"
    And I turn editing mode on
    And I follow "Week 1"

@javascript
Scenario: Upload file with copyright status not yet identified will display alert box
    Given I add a "File" to section "1" and I fill the form with:
      | Name | Test file with I own copyright status |
      | Description | I will assign I own copyright to this file |
    And I upload "lib/tests/fixtures/empty.txt" file to "Select files" filepicker
    And I press "Save and return to course"