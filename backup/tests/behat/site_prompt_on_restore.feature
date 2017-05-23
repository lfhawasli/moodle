@ucla @backup @CCLE-6585 @core_edit
Feature: Prompt site type when restoring an orphan collab site
  As an admin
  I want to restore a collab site with a site type
  So that there are no orphan collab sites

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1 |
    And I log in as "admin"
    And I am on site homepage
    And I follow "Course 1"
    And I turn editing mode on

  @javascript
  Scenario: Restore a course in a new course. Check that site type is required.
    Given I navigate to "Backup" node in "Course administration"
    And I press "Jump to final step"
    And I press "Continue"
    And I should see "Course backup area"
    And I click on "Restore" "link" in the "backup-files-table" "table"
    And I press "Continue"
    # Select first site category in list
    And I click on "//div['bcs-new-course']/descendant::div[@class='restore-course-search']/descendant::input[@type='radio']" "xpath_element"
    And I click on "//div['bcs-new-course']/descendant::input[@type='submit'][@value='Continue']" "xpath_element"
    And I press "Next"
    And I press "Next"
    Then I should see "Missing site type"

  @javascript
  Scenario: Restore a course in a new course. Check that selected site type persists.
    When I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name | Course 1 restored in a new course |
      | Schema | Site type | instruction |
    Then I should see "Course 1 restored in a new course"
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I should see "Instruction (degree-related)" in the "fitem_id_indicator" "region"
