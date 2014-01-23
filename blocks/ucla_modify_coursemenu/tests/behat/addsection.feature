@ucla @block_ucla_modify_coursemenu
Feature: Adding a section
  As a teacher
  I need to be able to add new sections
  So that I can organize my context

  Background: UCLA environment and srs site exists
    Given I am in a ucla environment
    And A ucla srs site exists

  @javascript
  Scenario: Log in and modify section
    Given I log in as ucla "editinginstructor"
    And I go to a ucla srs site
    And I turn editing mode on
    And I follow "Modify sections"
    And I wait "2" seconds
    And I press "Add new section"
    Then I should see "New"
    And I press "Save changes"
    Then I should see "The sections have been successfully updated."
