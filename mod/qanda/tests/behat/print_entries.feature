@ucla @mod @mod_qanda @CCLE-4677
Feature: Print Q&A entries, link to Q&A entries
  In order to utilize the Q&A module
  As a user
  I want to link to a question and print Q&A entries

  Background:
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

  Scenario: Print all Q&A entries
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Q&A test"
    And I follow "Printer-friendly version"
    Then I should see "What is the answer to life, the universe, and everything?"
    And I should see "What is my name?"
    And I should see "Your name is Sam."

 @javascript
  Scenario: Print a particular Q&A entry by using Permalink
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Q&A test"
    And I follow "Permalink"
    Then I should see "What is my name?"
    And I should not see "What is the answer to life"
    When I follow "Printer-friendly version"
    Then I should see "What is my name?"
    And I should see "Your name is Sam."