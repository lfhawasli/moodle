@ucla @mod @mod_qanda
Feature: View pending questions
  As an instructor
  I want to see pending questions from students

Scenario: Make sure instructors can view pending questions
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
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Q&A" to section "1" and I fill the form with:
      | Name | Q&A test |
      | Description | Description |
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I follow "Q&A test"
    And I press "Ask a question"
    And I set the following fields to these values:
      | Question | What is the answer to life, the universe, and everything? |
    And I press "Save changes"
    And I log out

    # Make sure that instructor view pending questions.
    When I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Q&A test"
    Then I should see "Questions Pending (1)"
    And I follow "Questions Pending (1)"
    Then I should see "What is the answer to life, the universe, and everything?"
    Then I should see "Posted by: Student S1 (student1@asd.com)"
    And I log out