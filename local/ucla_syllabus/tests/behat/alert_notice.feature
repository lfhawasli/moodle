@ucla @local_ucla_syllabus
Feature: Alert notice
    As an instructor
    I want to be able to be prompted to upload a syllabus
    So that I can either upload one, delay, or ignore.

Background:
   Given I am in a ucla environment
   And the following "users" exist:
       | username | firstname | lastname | email |
       | teacher1 | Teacher | 1 | teacher1@asd.com |
   And the following ucla "sites" exist:
       | fullname | shortname | type |
       | Course 1 | C1 | srs |
   And the following ucla "enrollments" exist:
       | user | course | role |
       | teacher1 | C1 | editingteacher |
   When I log in as "teacher1"
   And I am on "Course 1" course homepage
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

@javascript
Scenario: Make sure alert only shows up for current term and future terms
    # Default term that a course is built for is 14W.
    Given it is "Spring 2014" term "4th" week
    When I reload the page
    # If in Spring, but course is in Winter, course is in the past.
    Then I should not see "A syllabus has not been added to your site, would you like to add one now?"
    Given it is "Fall 2013" term "4th" week
    When I reload the page
    # If in Fall, but course is in Winter, course is in the future.
    Then I should see "A syllabus has not been added to your site, would you like to add one now?" in the ".ucla-format-notice-box" "css_element"
