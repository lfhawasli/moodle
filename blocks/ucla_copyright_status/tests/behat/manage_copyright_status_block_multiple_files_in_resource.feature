@ucla @block_ucla_copyright_status @CCLE-4396
Feature: Handle copyright status for resources containing multiple files.
As an instructor
I want to see each file in a resource separately
So that I can accurately indicate their copyright statuses

Background:
    Given I am in a ucla environment
    And the following "users" exist:
    | username | firstname | lastname | email |
    | teacher1 | Teacher | 1 | teacher1@abc.com |
    And the following ucla "sites" exist:
    | fullname | shortname | type | numsections |
    | course 1 | C1 | srs | 3 |
    And the following ucla "enrollments" exist:
    | user | course | role |
    | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I browse to site "C1"
    And I turn editing mode on
    And I follow "Week 1"

@javascript
Scenario Outline: Resource with multiple files
    Given I add a "<resource>" to section "1"
    And I set the field "Name" to "Hello"
    And I upload "lib/tests/fixtures/empty.txt" file to "<filepicker>" filemanager
    And I upload "lib/tests/fixtures/tabfile.csv" file to "<filepicker>" filemanager
    And I press "Save and return to course"
    When I follow "Manage copyright"
    Then I should see "<filedisplay1>"
    And I should see "<filedisplay2>"
    When I set the field with xpath "//td[contains(., 'empty.txt')]//select" to "I own the copyright"
    And I press "Save changes"
    Then I should not see "empty.txt"
    But I should see "tabfile.csv"
    Examples:
        | resource | filepicker   | filedisplay1                 | filedisplay2                        |
        | File     | Select files | empty.txt (Hello: main file) | tabfile.csv (Hello: secondary file) |
        | Folder   | Files        | empty.txt                    | tabfile.csv                         |
