@ucla @mod @mod_qanda
Feature: Import/Export existing Q&As
  As an instructor
  I want to be able to import or export Q&As
  So I can better organize and backup course materials.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | T1 | teacher1@asd.com |
      | student1 | Student | S1 | student1@asd.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Q&A" to section "0" and I fill the form with:
      | Name | Q&A test |
      | Description | Description |

  Scenario: Export Q&A
    When I follow "Q&A test"
    And I follow "Export entries"
    Then I should see "Export entries to XML file"

  @javascript
  Scenario: Import Q&A to replace current entry
    When I follow "Q&A test"
    And I follow "Import entries"
    And I upload "mod/qanda/tests/fixtures/import.xml" file to "File to import" filemanager
    And I press "Submit"
    And I press "Continue"
    Then I should see "What year was UCLA founded?"
    And I should see "1919"

  @javascript
  Scenario: Import Q&A as a new entry
    When I follow "Q&A test"
    And I follow "Import entries"
    And I upload "mod/qanda/tests/fixtures/import.xml" file to "File to import" filemanager
    And I set the field "id_dest" to "New Q&A"
    And I press "Submit"
    And I press "Continue"
    And I follow "QA import test"
    Then I should see "What year was UCLA founded?"
    And I should see "1919"