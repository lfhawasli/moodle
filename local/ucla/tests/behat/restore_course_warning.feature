@ucla @local_ucla @CCLE-4416
Feature: Overwriting a course with via a restore
   In order to prevent losing data while restoring a course
   As a moodle admin
   I need to be able to see if I am overwriting a course with content

   Background:
      Given I am in a ucla environment
      And the following "users" exist:
         | username | firstname | lastname | email |
         | teacher | Teacher | 1 | teacher@asd.com |
      And the following "courses" exist:
         | fullname | shortname | format |
         | Source | source | ucla |
         | Target | target | ucla |
      And I log in as "admin"
      And I backup "Source" course using this options:
         | Confirmation | Filename | source.mbz |

   @javascript
   Scenario: Restoring into this course with no content
      When I click on "Restore" "link" in the "source.mbz" "table_row"
      And I press "Continue"
      # Radio buttons in backup/restore are not properly labeled, so have to
      # refer to "Delete the contents of this course and then restore" with this
      # complicated method (see backup/util/ui/tests/behat/behat_backup.php:
      # i_merge_backup_into_current_course_deleting_its_contents.
      And I click on "//div[contains(concat(' ', normalize-space(@class), ' '), 'bcs-current-course')]/descendant::input[@type='radio'][@name='target'][@value='0']" "xpath_element"
      Then I should not see "Course deletion warning"

   @javascript
   Scenario: Restoring into this course with content
      Given I am on homepage
      And I follow "Courses"
      And I follow "Source"
      And I turn editing mode on
      And I follow the "Week 1" section in the ucla site menu
      When I add a "Page" to section "1" and I fill the form with:
         | Name | Page |
         | Page content | Some text |
      And I follow "Restore"
      And I click on "Restore" "link" in the "source.mbz" "table_row"
      And I press "Continue"
      # Radio buttons in backup/restore are not properly labeled, so have to
      # refer to "Delete the contents of this course and then restore" with this
      # complicated method (see backup/util/ui/tests/behat/behat_backup.php:
      # i_merge_backup_into_current_course_deleting_its_contents.
      And I click on "//div[contains(concat(' ', normalize-space(@class), ' '), 'bcs-current-course')]/descendant::input[@type='radio'][@name='target'][@value='0']" "xpath_element"
      Then I should see "Course deletion warning"
      And I click on "Continue" "button" in the ".confirmation-dialogue" "css_element"
      And I should see "Restore settings"

   @javascript
   Scenario: Restoring into existing course with no content
      When I click on "Restore" "link" in the "source.mbz" "table_row"
      And I press "Continue"
      # Radio buttons in backup/restore are not properly labeled, so have to
      # Refer to "Delete the contents of the existing course and then restore" 
      # with this complicated method.
      When I click on "//div[contains(concat(' ', normalize-space(@class), ' '), 'bcs-existing-course')]/descendant::input[@type='radio'][@name='target'][@value='3']" "xpath_element"
      And I click on "targetid" "radio" in the "Target" "table_row"
      Then I should not see "Course deletion warning"

   @javascript
   Scenario: Restoring into existing course with content
        # First go to target course and add content.
      Given I am on homepage
      And I follow "Courses"
      And I follow "Target"
      And I turn editing mode on
      And I follow the "Week 1" section in the ucla site menu
      And I add a "Page" to section "1" and I fill the form with:
         | Name | Page |
         | Page content | Some text |
      And I am on homepage
      And I follow "Courses"
      And I follow "Source"
      And I follow "Restore"
      And I click on "Restore" "link" in the "source.mbz" "table_row"
      And I press "Continue"
      # Radio buttons in backup/restore are not properly labeled, so have to
      # Refer to "Delete the contents of the existing course and then restore" 
      # with this complicated method.
      When I click on "//div[contains(concat(' ', normalize-space(@class), ' '), 'bcs-existing-course')]/descendant::input[@type='radio'][@name='target'][@value='3']" "xpath_element"
      And I click on "targetid" "radio" in the "Target" "table_row"
      Then I should see "Course deletion warning"
      And I click on "Continue" "button" in the ".confirmation-dialogue" "css_element"
      And I should see "Restore settings"
