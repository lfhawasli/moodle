@ucla @block_ucla_easy_upload
Feature: Easy Upload
  As an instructor
  I want to upload files, links, activities, and resources
  So that I can update my course

  Background:
    Given I am in a ucla environment
    And a ucla srs site exists
    And I log in as instructor
    And I go to a ucla srs site
    When I press "Control Panel"

  @javascript
  Scenario: Upload a file via Control Panel
    And I follow "Upload a file"
    And I upload "blocks/ucla_easyupload/tests/fixtures/test.txt" file to "Select file" filepicker
    And I fill in "Name" with "testupload1"
    And I press "Save changes"
    Then I should see "Successfully added file to section."
    When I press "Return to course"
    Then I should see "testupload1"

  @javascript
  Scenario: Add a link via Control Panel
    And I follow "Add a link"
    And I fill in "Enter link URL" with "www.ucla.edu"
    And I fill in "Name" with "UCLA homepage"
    And I press "Save changes"
    Then I should see "Successfully added link to section."
    When I press "Return to course"
    Then I should see "UCLA homepage"
    When I follow "UCLA homepage"
    Then I should see "Click http://www.ucla.edu link to open resource."

  @javascript
  Scenario: Add an activity via Control Panel
    And I follow "Add an activity"
    And I select "Wiki" from "Activity"
    And I press "Save changes"
    Then I should see "Adding a new Wiki"

  @javascript
  Scenario: Add a resource via Control Panel
    And I follow "Add a resource"
    And I select "File" from "Resource"
    And I press "Save changes"
    Then I should see "Adding a new File"