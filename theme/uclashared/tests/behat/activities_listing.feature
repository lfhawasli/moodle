@ucla @theme_uclashared
Feature: Display links to all activities available in the course
  In order to list all activities of a certain type for a course
  As an instructor
  When I add an activity the site menu should have a quick link to that activity type

  Background:
    Given I am in a ucla environment
    And the following ucla "sites" exist:
      | fullname | shortname | type |
      | Course 1 | C1 | srs |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
    And the following ucla "enrollments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I browse to site "C1"
    And I turn editing mode on
    And I follow the "Week 1" section in the ucla site menu

  Scenario: Make sure "Assignments" section exists and works
    When I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment to section 1 |
      | Description | Test assignment to section 1 description |
    Then I should see "Assignments" in the ucla site menu
    When I follow the "Assignments" section in the ucla site menu
    Then I should see "Test assignment to section 1" in the "region-main" "region"

  Scenario: Make sure "Forums" section exists and works
    When I add a "Forum" to section "1" and I fill the form with:
      | Forum name | Test forum to section 1 |
      | Description | Test forum to section 1 description |
    Then I should see "Forums" in the ucla site menu
    When I follow the "Forums" section in the ucla site menu
    Then I should see "Test forum to section 1" in the "region-main" "region"
    And I should see "Announcements" in the "region-main" "region"
    And I should see "Discussion forum" in the "region-main" "region"

  Scenario: Make sure "Chats" section exists and works
    When I add a "Chat" to section "1" and I fill the form with:
      | Name of this chat room | Test chat to section 1 |
      | Description | Test chat to section 1 description |
    Then I should see "Chats" in the ucla site menu
    When I follow the "Chats" section in the ucla site menu
    Then I should see "Test chat to section 1" in the "region-main" "region"

  Scenario: Make sure "Choices" section exists and works
    When I add a "Choice" to section "1" and I fill the form with:
      | Choice name | Test choice to section 1 |
      | Description | Test choice to section 1 description |
      | Option 1 | option 1 |
    Then I should see "Choices" in the ucla site menu
    When I follow the "Choices" section in the ucla site menu
    Then I should see "Test choice to section 1" in the "region-main" "region"

  Scenario: Make sure "Glossaries" section exists and works
    When I add a "Glossary" to section "1" and I fill the form with:
      | Name | Test glossary to section 1 |
      | Description | Test glossary to section 1 description |
    Then I should see "Glossaries" in the ucla site menu
    When I follow the "Glossaries" section in the ucla site menu
    Then I should see "Test glossary to section 1" in the "region-main" "region"

  Scenario: Make sure "Quizzes" section exists and works
    When I add a "Quiz" to section "1" and I fill the form with:
      | Name | Test quiz to section 1 |
      | Description | Test quiz to section 1 description |
    Then I should see "Quizzes" in the ucla site menu
    When I follow the "Quizzes" section in the ucla site menu
    Then I should see "Test quiz to section 1" in the "region-main" "region"

  Scenario: Make sure "Wikis" section exists and works
    When I add a "Wiki" to section "1" and I fill the form with:
      | Wiki name | Test wiki to section 1 |
      | Description | Test wiki to section 1 description |
      | First page name | Test wiki first page |
    Then I should see "Wikis" in the ucla site menu
    When I follow the "Wikis" section in the ucla site menu
    Then I should see "Test wiki to section 1" in the "region-main" "region"