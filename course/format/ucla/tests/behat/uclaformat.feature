@ucla @course_format_ucla @check_format_test
Feature: Control Panel
  As an instructor
  I want to confirm that course is showing in "UCLA Format"

 Background:
Given I am in a ucla environment
And the following "users" exists:
| username | firstname | lastname | email |
| teacher1 | Teacher | 1 | teacher1@asd.com |
And the following ucla "sites" exists:
| fullname | shortname | type |
| course 1 | C1 | srs |
And the following ucla "enrollments" exists:
| user | course | role |
| teacher1 | C1 | editingteacher |

 @javascript
  Scenario: confirm ucla format    
    And I log in as ucla "teacher1"
    And I browse to site "C1"
    And I follow "Edit settings"
    And I follow "Course format"
    Then I should see "UCLA format"
