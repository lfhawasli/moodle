@ucla @mod @mod_qanda
Feature: Edit unanswered questions
  As a student
  I want to edit unanswered questions

  @javascript
  Scenario: Make sure students can edit unanswered questions
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

    # Make sure that students can edit an unanswered question
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Q&A test"
    And I follow "Edit"
    And I set the following fields to these values:
      | Question | I changed my question |
    And I press "Save changes"
    Then I should see "What is my name?"
    Then I should see "Your name is Sam."
    Then I should see "I changed my question"
    And I log out