@ucla @block_ucla_modify_coursemenu @CCLE-6289
Feature: Uploading content to sections
  As an instructor
  When I hide a section using Modify sections tool
  I want the content in the sections to be hidden as well

  @javascript
  Scenario: Hidding sections via modify sections
    Given I am in a ucla environment
    And the following "users" exist:
    | username | firstname | lastname | email |
    | teacher1 | Teacher | 1 | teacher1@whatever.com |
    And the following "courses" exist:
    | fullname | shortname | format | numsections |
    | course1 | C1 | ucla | 10 |
    And the following "course enrolments" exist:
    | user | course | role |
    | teacher1 | C1 | editingteacher |
    # Uploading content
    And I log in as "teacher1"
    And I follow "course1"
    And I follow "Admin panel"
    And I follow "Upload a file"
    And I upload "lib/tests/fixtures/empty.txt" file to "Select file" filemanager
    And I set the field "Name" to "visible_file"
    And I set the field "Add to section" to "Week 1"
    And I press "Save changes"
    And I should see "Successfully added file to section." in the "region-main" "region"
    And I press "Return to section"
    And I turn editing mode on
    And "visible_file" activity should be visible
    # Making section hidden
    When I follow "Modify sections"
    And I set the field "hidden-1" to "1"
    And I press "Save changes"
    And I should see "Success!"
    And I press "Return to site"
    # Verify that "Week 1" section and content is hidden.
    Then section "1" should be hidden
    And "visible_file" activity should be hidden
    