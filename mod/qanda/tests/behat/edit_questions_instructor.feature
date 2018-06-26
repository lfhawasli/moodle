@ucla @mod @mod_qanda @CCLE-4677
Feature: View pending questions
  In order to utilize the Q&A module
  As an instructor
  I want to view, edit, and/or delete pending and answered questions

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1 |
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

  @javascript
  Scenario: Make sure instructors can view pending questions
    Then I should see "Questions Pending (1)"
    When I follow "Questions Pending (1)"
    Then I should see "What is the answer to life, the universe, and everything?"
    And I should see "Posted by: Student S1 (student1@asd.com)"

  Scenario: As an instructor, delete answered and pending questions
    When I follow "Delete"
    And I press "Continue"
    Then I should not see "Your name is Sam."
    When I follow "Questions Pending (1)"
    And I follow "Delete"
    And I press "Continue"
    Then I should not see "What is the answer to life, the universe, and everything?"

  Scenario: As an instructor, modify a question and/or answer
    When I follow "Edit"
    And I set the following fields to these values:
      | Question | What is my full name? |
      | Answer | Your full name is Sam Samson. |
    And I press "Save changes"
    Then I should see "Your full name is Sam Samson."