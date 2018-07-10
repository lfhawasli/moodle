@ucla @local_ucla @core_backup @core_edit @CCLE-4929
Feature: Remove email restriction from restore/import
  In order to restore a course
  As an admin
  I want to be able to restore a course even if user emails in the backup file do not match user emails on the server

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
      | student1 | Student   | 1        | student1@asd.com |
    And the following "courses" exist:
        | fullname | shortname |
        | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student |

  @javascript
  Scenario: Backup and change user emails
    # Add course content as a admin, so that the firstaccess is unchanged.
    # Add content as teacher.
    Given I log in as "admin"
    And I follow "Courses"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Forum" to section "1" and I fill the form with:
      | Forum name | Test forum name |
      | Description | Test forum description |
    And I add a new discussion to "Test forum name" forum with:
      | Subject | Discussion 1 |
      | Message | Discussion contents 1, first message |
    And I reply "Discussion 1" post from "Test forum name" forum with:
      | Subject | Reply 1 to discussion 1 |
      | Message | Discussion contents 1, second message |
    And I log out
    # Add course content as a student: reply to a discussion.
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I reply "Discussion 1" post from "Test forum name" forum with:
      | Subject | Reply 2 to discussion 1 |
      | Message | Discussion contents 2, third message |
    And I log out
    # Backup the course.
    Given I log in as "admin"
    And I follow "Courses"
    And I am on "Course 1" course homepage
    And I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    # Change the teacher and student's emails.
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "a[title=Edit]" "css_element" in the "teacher1@asd.com" "table_row"
    And I set the field "Email address" to "newteacher@asd.com"
    And I click on "Update profile" "button"
    And I click on "a[title=Edit]" "css_element" in the "student1@asd.com" "table_row"
    And I set the field "Email address" to "newstudent@asd.com"
    And I click on "Update profile" "button"
    # Change the config variable to trigger code
    And I set the private config setting "forcedifferentsitecheckingusersonrestore" to "1";
    # Login as teacher so that the firstaccess value is updated, causing the
    # error to appear if our core edit wasn't made.
    And I log out
    Given I log in as "teacher1"
    And I log out
    And I log in as "admin"
    # Restore the course.
    When I am on homepage
    And I follow "Courses"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I follow "Restore"
    And I restore "test_backup.mbz" backup into a new course using this options:
    | Schema | Site type | test |
    Then I should not see "Trying to restore user 'teacher1' from backup file will cause conflict" in the "region-main" "region"
