@ucla @local_ucla_syllabus @javascript
Feature: Use an URL as a syllabus
    As an instructor
    I want to be able to use an URL as my syllabus
    So that I can direct students to content on another website I control

Background: Specifying URL as a syllabus
   Given I am in a ucla environment
   And the following "users" exist:
       | username | firstname | lastname | email |
       | teacher1 | Teacher | 1 | teacher1@asd.com |
       | student1 | Student | 1 | student1@asd.com |
   And the following ucla "sites" exist:
       | fullname | shortname | type |
       | Course 1 | C1 | srs |
   And the following ucla "enrollments" exist:
       | user | course | role |
       | teacher1 | C1 | editingteacher |
       | student1 | C1 | student |

Scenario: Check warning for unsecure urls
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I follow the "Syllabus (empty)" section in the ucla site menu
    And I follow "Add syllabus"
    And I click on "URL" "radio"
    And I set the field "syllabus_url" to "http://ucla.edu"
    And I press "Save changes"
    Then I should see "The syllabus URL (http://ucla.edu) does not use https, and will not be embedded in the page for security reasons. It will display as a link instead. Do you want to continue?"
    And I press "Continue"
    And I turn editing mode off
    And I should see "http://ucla.edu"
    And I turn editing mode on
    When I follow "Edit"
    And I set the field "syllabus_url" to "https://ucla.edu"
    And I press "Save changes"
    Then I should not see "The syllabus URL (https://ucla.edu) does not use https, and will not be embedded in the page for security reasons. It will display as a link instead. Do you want to continue?"

Scenario: Viewing URL as a student
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I follow the "Syllabus (empty)" section in the ucla site menu
    And I follow "Add syllabus"
    And I click on "URL" "radio"
    And I set the field "syllabus_url" to "http://ucla.edu"
    And I press "Save changes"
    Then I should see "The syllabus URL (http://ucla.edu) does not use https, and will not be embedded in the page for security reasons. It will display as a link instead. Do you want to continue?"
    Given I press "Continue"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And  I follow the "Syllabus" section in the ucla site menu
    Then I should see "Syllabus" in the "region-main" "region"
    And I should see "http://ucla.edu" in the "region-main" "region"
    #Check nested syllabus site does not show up
    And "//*[contains(@id, 'resourceobject')]" "xpath_element" should not exist
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I follow the "Syllabus" section in the ucla site menu
    When I follow "Edit"
    Then I set the field "syllabus_url" to "https://ucla.edu"
    And I press "Save changes"
    And I log out
    And I log in as "student1"
    When I follow "Course 1"
    And  I follow the "Syllabus" section in the ucla site menu
    Then I should see "Syllabus" in the "region-main" "region"
    And I should see "https://ucla.edu" in the "region-main" "region"
    #Check nested syllabus site does show up
    And "//*[contains(@id, 'resourceobject')]" "xpath_element" should exist

Scenario: Viewing URL as an instructor
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I follow the "Syllabus (empty)" section in the ucla site menu
    And I follow "Add syllabus"
    And I click on "URL" "radio"
    And I set the field "syllabus_url" to "http://ucla.edu"
    And I press "Save changes"
    Then I should see "The syllabus URL (http://ucla.edu) does not use https, and will not be embedded in the page for security reasons. It will display as a link instead. Do you want to continue?"
    And I press "Continue"
    And I turn editing mode off
    Then I should see "Syllabus" in the "region-main" "region"
    And I should see "http://ucla.edu" in the "region-main" "region"
    #Check nested syllabus site does not show up
    And "//*[contains(@id, 'resourceobject')]" "xpath_element" should not exist
    And I turn editing mode on
    And I follow "Edit"
    And I set the field "syllabus_url" to "https://ucla.edu"
    And I press "Save changes"
    And I turn editing mode off
    Then I should see "Syllabus" in the "region-main" "region"
    And I should see "https://ucla.edu" in the "region-main" "region"
    #Check nested syllabus site does show up
    And "//*[contains(@id, 'resourceobject')]" "xpath_element" should exist
