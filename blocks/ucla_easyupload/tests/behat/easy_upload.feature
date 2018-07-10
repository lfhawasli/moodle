@ucla @block_ucla_easyupload
Feature: Easy Upload
  As an instructor
  I want to upload files, links, activities, and resources
  So that I can update my course

  Background:
    Given I am in a ucla environment
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
    And the following ucla "sites" exist:
        | fullname | shortname | type |
        | course 1 | C1 | srs |
    And the following ucla "enrollments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I follow "course 1"
    And I follow "Admin panel"

  @javascript
  Scenario: Upload a file to the Site Info section via Admin panel
    When I follow "Upload a file"
    And I upload "lib/tests/fixtures/empty.txt" file to "Select file" filemanager
    And I set the field "Name" to "testupload1"
    And I press "Save changes"
    Then I should see "Successfully added file to section." in the "region-main" "region"
    When I press "Return to course"
    Then I should see "testupload1" in the "region-main" "region"

  @javascript
  Scenario: Upload a file to a specific Week section via Admin panel
    When I follow "Upload a file"
    And I upload "lib/tests/fixtures/empty.txt" file to "Select file" filemanager
    And I set the field "Name" to "testupload2"
    And I set the field "Add to section" to "Week 5"
    And I press "Save changes"
    Then I should see "Successfully added file to section." in the "region-main" "region"
    When I press "Return to section"
    Then I should be on section "Week 5"
    And I should see "testupload2" in the "region-main" "region"
    When I follow "Site info"
    Then I should not see "testupload2" in the "region-main" "region"

  @javascript
  Scenario: Add a link via Admin panel
    When I follow "Add a link"
    And I set the field "Enter link URL" to "www.ucla.edu"
    And I set the field "Name" to "UCLA homepage"
    And I press "Save changes"
    Then I should see "Successfully added link to section." in the "region-main" "region"
    When I press "Return to course"
    Then I should see "UCLA homepage" in the "region-main" "region"
    When I follow "UCLA homepage"
    Then I should see "UCLA"

  Scenario: Add an activity via Admin panel
    When I follow "Add an activity"
    And I set the field "Activity" to "Wiki"
    And I press "Save changes"
    Then I should see "Adding a new Wiki" in the "region-main" "region"

  Scenario: Add a resource via Admin panel
    When I follow "Add a resource"
    And I set the field "Resource" to "File"
    And I press "Save changes"
    Then I should see "Adding a new File" in the "region-main" "region"