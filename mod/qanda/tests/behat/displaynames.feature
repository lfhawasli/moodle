@ucla @mod @mod_qanda @CCLE-4443
Feature: Display name and email of student who posted a question
  As an instructor
  I want to see the name and email of a student who posted a question
  So that I can contact students directly

# Note, cannot run this test with javascript, because the step to answer the
# question fails, since this is not a standard Moodle form.
Scenario: Make sure that only instructors can see posts names/emails
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | T1 | teacher1@asd.com |
      | student1 | Student | S1 | student1@asd.com |
      | student2 | Student | S2 | student2@asd.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
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
    And I log out

    # Make sure that instructor can see names/emails.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Q&A test"
    And I follow "Questions Pending (1)"
    Then I should see "Posted by: Student S1 (student1@asd.com)"
    And I follow "Answer"
    And I set the following fields to these values:
      | Answer | 42 |
    And I press "Save changes"
    And I should see "Posted by: Student S1 (student1@asd.com)"
    And I log out

    # Make sure that students cannot see names/emails.
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Q&A test"
    Then I should not see "Posted by: Student S1 (student1@asd.com)"
    And I log out
    When I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Q&A test"
    Then I should not see "Posted by: Student S1 (student1@asd.com)"


