@ucla @block_ucla_modify_coursemenu
Feature: Uploading content to sections
  As an instructor
  I need to upload content to both hidden and visible sections
  And visible sections remain accessible by student
  And hidden sections remain inaccessble by student

  Background: I am in a UCLA environment
    Given I am in a ucla environment
    And the following "users" exist:
    | username | firstname | lastname | email |
    | teacher1 | Teacher | 1 | teacher1@whatever.com |
    | student1 | Student | 1 | student1@whatever.com |
    And the following "courses" exist:
    | fullname | shortname | format | numsections |
    | course1 | C1 | ucla | 10 |
    And the following "course enrolments" exist:
    | user | course | role |
    | teacher1 | C1 | editingteacher |
    | student1 | C1 | student |

  @javascript
  Scenario: Add a hidden section and upload content
    # Add a hidden section.
    When I log in as "teacher1"
    And I follow "course1"
    And I turn editing mode on
    And I follow "Modify sections"
    And I press "Add new section"
    And I wait "5" seconds
    Then I should see "New"
    And I set the field "title-new_0" to "new1"
    And I click on "hidden-new_0" "checkbox"
    And I press "Save changes"
    And I wait "5" seconds
    Then I should see "Success!"
    And I press "Return to site"
    And I follow "Show all"
    # Verify that "new1" section is hidden.
    Then section "11" should be hidden

    # Upload content to hidden section.
    When I press "Admin panel"
    And I follow "Upload a file"
    And I wait "5" seconds
    And I upload "lib/tests/fixtures/empty.txt" file to "Select file" filemanager
    And I set the field "Name" to "hidden_file"
    And I set the field "Add to section" to "new1"
    And I press "Save changes"
    Then I should see "Successfully added file to section." in the "region-main" "region"
    When I press "Return to section"
    Then "hidden_file" activity should be hidden
    And I log out

    # Student should accessible to visible section, but not hidden section.
    Given I log in as "student1"
    And I follow "course1"
    When I follow "Show all"
    Then I should not see "new1"
    And I log out

    # Upload content to visible section.
    Given I log in as "teacher1"
    And I follow "course1"
    And I follow "Admin panel"
    And I follow "Upload a file"
    And I wait "5" seconds
    And I upload "lib/tests/fixtures/empty.txt" file to "Select file" filemanager
    And I set the field "Name" to "visible_file"
    And I set the field "Add to section" to "Week 1"
    And I press "Save changes"
    Then I should see "Successfully added file to section." in the "region-main" "region"
    When I press "Return to section"
    And I turn editing mode on
    And "visible_file" activity should be visible
    And I follow "Show all"
    And I show section "11"
    And I log out

    # Student should accessible to visible section, but not hidden section.
    Given I log in as "student1"
    And I follow "course1"
    When I follow "Show all"
    Then I should see "visible_file"
    And I should see "hidden_file"
