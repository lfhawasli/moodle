@ucla @mod @mod_qanda @CCLE-4677
Feature: Search Q&A entries for specific terms
  In order to efficiently utilize the Q&A module
  As a user
  I want to search Q&A entries

  @javascript
  Scenario:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1 |
    And the following "users" exist:
      | username |
      | teacher1 |
      | student1 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Q&A" to section "1" and I fill the form with:
      | Name | Q&A test |
      | Description | Description |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Q&A test"
    And I follow "Ask a question"
    And I set the following fields to these values:
      | Question | What is the answer to life, the universe, and everything? |
    And I press "Save changes"
    And I follow "Ask a question"
    And I set the following fields to these values:
      | Question | What is my name? |
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Q&A test"
    And I follow "Questions Pending (2)"
    And I follow "Answer"
    And I set the following fields to these values:
      | Answer | Your name is Sam. |
    And I press "Save changes"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Q&A test"
    And I set the field "hook" to "universe everything"
    And I press "Search"
    Then I should see "What is the answer to life"
    And I should not see "Your name is Sam."