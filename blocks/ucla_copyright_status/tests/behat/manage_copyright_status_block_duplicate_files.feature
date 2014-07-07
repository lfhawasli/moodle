@ucla @block_ucla_copyright_status
Feature: Assign copyright status for duplicate files.
    As an instructor
    I want to be able to assign the one copyright status to the same file that I uploaded twice.

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
Scenario: Upload same file twice and copyright status drop down only show once for the two files
    Given I add a "File" to section "1" and I fill the form with:
      | Name | Test file1 |
      | Description | I upload this file the first time |
    And I upload "lib/tests/fixtures/empty.txt" file to "Select files" filepicker
    And I press "Save and return to course"
    And I follow "Manage copyright"
    And I wait "2" seconds
    And I select "All" from "filter_copyright"
    And I wait "2" seconds
    Then I should see "Test file1"
    And I follow "Week 1"
    And I add a "File" to section "1" and I fill the form with:
      | Name | Test file2 |
      | Description | I upload this file the second time |
    And I upload "lib/tests/fixtures/empty.txt" file to "Select files" filepicker
    And I press "Save and return to course"
    And I follow "Manage copyright"
    And I wait "2" seconds
    And I select "All" from "filter_copyright"
    And I wait "2" seconds
    And I should see "Test file1" in the "//tr[@class='r0 lastrow']/descendant::td[@class='cell c0']" "xpath_element"
    And I should see "Test file2" in the "//tr[@class='r0 lastrow']/descendant::td[@class='cell c0']" "xpath_element"
    And "//tr[@class='r0 lastrow']/descendant::td[@class='cell c0']/descendant::select[contains(@name,'filecopyright_')]" "xpath_element" should exists
    And "Test file1" "link" should appear before "//select[contains(@name,'filecopyright_')]" "xpath_element"
    And "Test file2" "link" should appear before "//select[contains(@name,'filecopyright_')]" "xpath_element"
