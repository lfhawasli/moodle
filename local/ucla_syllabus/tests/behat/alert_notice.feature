@ucla @local_ucla_syllabus
Feature: Alert notice
    As an instructor
    I want to be able to be prompted to upload a syllabus
    So that I can either upload one, delay, or ignore.

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
   When I log in as "teacher1"
   And I browse to site "C1"
   Then I should see "A syllabus has not been added to your site, would you like to add one now?" in the ".ucla-format-notice-box" "css_element"

Scenario: "Yes"
    When I press "Yes"
    Then I should see "Syllabus manager"

Scenario: "Ask me later"
    When I press "Ask me later"
    Then I should see "Syllabus reminder set." in the ".alert-success" "css_element"

Scenario: "No, don't ask again"
    When I press "No, don't ask again"
    Then I should see "You will no longer be prompted to add a syllabus." in the ".alert-success" "css_element"
