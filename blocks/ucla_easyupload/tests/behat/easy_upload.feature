@ucla @block_ucla_easy_upload
Feature: Easy Upload
  As an instructor
  I want to upload files, links, activities, and resources
  So that I can update my course

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
    And I log in as ucla "teacher1"
    And I browse to site "C1"
    And I press "Control Panel"

  @javascript
  Scenario: Upload a file via Control Panel
    When I follow "Upload a file"
    And I upload "blocks/ucla_easyupload/tests/fixtures/test.txt" file to "Select file" filepicker
    And I fill in "Name" with "testupload1"
    And I press "Save changes"
    Then I should see "Successfully added file to section."
    When I press "Return to course"
    Then I should see "testupload1"

  @javascript
  Scenario: Add a link via Control Panel
    When I follow "Add a link"
    And I fill in "Enter link URL" with "www.ucla.edu"
    And I fill in "Name" with "UCLA homepage"
    And I press "Save changes"
    Then I should see "Successfully added link to section."
    When I press "Return to course"
    Then I should see "UCLA homepage"
    When I follow "UCLA homepage"
    Then I should see "Click http://www.ucla.edu link to open resource."

#  @javascript
  Scenario: Add an activity via Control Panel
    When I follow "Add an activity"
    And I select "Wiki" from "Activity"
    And I press "Save changes"
    Then I should see "Adding a new Wiki"

#  @javascript
  Scenario: Add a resource via Control Panel
    When I follow "Add a resource"
    And I select "File" from "Resource"
    And I press "Save changes"
    Then I should see "Adding a new File"