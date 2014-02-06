@ucla @block_ucla_modify_coursemenu
Feature: Uploading content to sections
  As an instructor
  I need to upload content to both hidden and visible sections
  And visible sections remain accessible by student
  And hidden sections remain inaccessble by student

  Background: I am in a UCLA environment
    Given I am in a ucla environment
    And the following "users" exists:
    | username | firstname | lastname | email |
    | teacher1 | Teacher | 1 | teacher1@whatever.com |
    | student1 | Student | 1 | student1@whatever.com |
    And the following "courses" exists:
    | fullname | shortname | format | numsections |
    | course1 | C1 | ucla | 10 |
    And the following "course enrolments" exists:
    | user | course | role |
    | teacher1 | C1 | editingteacher |
    | student1 | C1 | student |

    @javascript
    Scenario: Add a hidden section and upload content
    # add a hidden section
    When I log in as ucla "teacher1"
    And I follow "course1"
    And I turn editing mode on
    And I follow "Modify sections"
    And I press "Add new section"
    And I wait "5" seconds
    Then I should see "New"
    And I fill in "title-new_0" with "new1"
    And I click on "hidden-new_0" "checkbox"
    And I press "Save changes"
    And I wait "5" seconds
    Then I should see "The sections have been successfully updated."
    And I press "Return to course"
    Then I should see "new1"

    # upload content to hidden section
    When I press "Control Panel"
    And I follow "Upload a file"
    # And I wait "5" seconds
    # And I upload "blocks/ucla_modify_coursemenu/tests/fixtures/yin.jpg" file to "Select file" filepicker
    # And I fill in "Name" with "hidden_yin"
    # And I select "new1" from "Add to section"
    # And I press "Save changes"
    # Then I should see "Successfully added file to section." in the "region-main" "region"
    # When I press "Return to course"
    # Then I should see "hidden_yin" in the "region-main" "region"
    Then I follow "Logout"

    # student should accessible to visible section, but not hidden section
    When I log in as ucla "student1"
    And I follow "course1"
    Then I should see "Week 1"
    And I should not see "new1"
    Then I follow "Logout"

    # upload content to visible section
    When I log in as ucla "teacher1"
    And I follow "course1"
    And I press "Control Panel"
    And I follow "Upload a file"
    # And I wait "5" seconds
    # And I upload "blocks/ucla_modify_coursemenu/tests/fixtures/dingdang.jpg" file to "Select file" filepicker
    # And I fill in "Name" with "visible_dingdang"
    # And I select "Week 1" from "Add to section"
    # And I press "Save changes"
    # Then I should see "Successfully added file to section." in the "region-main" "region"
    # When I press "Return to course"
    # Then I should see "visible_dingdang" in the "region-main" "region"
    Then I follow "Logout"

    # student should accessible to visible section, but not hidden section
    When I log in as ucla "student1"
    And I follow "course1"
    Then I should see "Week 1"
    And I should not see "new1"