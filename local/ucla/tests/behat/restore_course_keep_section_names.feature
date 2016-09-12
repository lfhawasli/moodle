@ucla @local_ucla @core_backup @CCLE-4808 @CCLE-3483 @core_edit
Feature: Restoring a course and keeping section names from backup
  In order to retain course information I'm importing into a new course
  As a course instructor
  I need to be able to import an old course and retain the original section names

  Background:
    Given I am in a ucla environment
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher | Teacher | 1 | teacher@asd.com |
    And the following ucla "sites" exist:
      | fullname | shortname | type |
      | Source | source | instruction |
      | Target | target | instruction |
    And the following ucla "enrollments" exist:
      | user | course | role |
      | teacher | source | editinginstructor |
      | teacher | target | editinginstructor |
    And I log in as "teacher"

  @javascript
  Scenario: Import a course and overwrite default section names
    When I am on homepage
    And I follow "Source"
    And I turn editing mode on
    And I follow the "Week 1" section in the ucla site menu
    And I click on "Edit" "link"
    And I follow "Edit section"
    And I set the following fields to these values:
      | Use default section name | 0 |
      | id_name | testsection1 |
    And I press "Save changes"
    And I follow the "Week 2" section in the ucla site menu
    And I click on "Edit" "link"
    And I follow "Edit section"
    And I set the following fields to these values:
      | Use default section name | 0 |
      | id_name | testsection2 |
    And I press "Save changes"
    And I import "Source" course into "Target" course using this options:
    Then I should see "testsection1"
    And I should see "testsection2"
    And I should see "Week 3" in the ucla site menu