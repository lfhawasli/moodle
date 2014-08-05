@ucla @local_ucla_syllabus
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
   And I log in as "teacher1"
   And I follow "Course 1"
   And I turn editing mode on
   And I follow the "Syllabus (empty)" section in the ucla site menu
   When I follow "Add syllabus"
   And I set the field "URL" to "http://ucla.edu"
   And I press "Save changes"
   And I log out

Scenario: Viewing URL as a student
    Given I log in as "student1"
    When I follow "Course 1"
    And  I follow the "Syllabus" section in the ucla site menu
    Then I should see "Syllabus" in the "region-main" "region"
    And I should see "http://ucla.edu" in the "region-main" "region"

Scenario: Viewing URL as an instructor
    Given I log in as "teacher1"
    When I follow "Course 1"
    And  I follow the "Syllabus" section in the ucla site menu
    Then I should see "Syllabus" in the "region-main" "region"
    And I should see "http://ucla.edu" in the "region-main" "region"