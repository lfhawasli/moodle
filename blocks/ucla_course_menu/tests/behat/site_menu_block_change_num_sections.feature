@ucla @block_ucla_course_menu
Feature: Change number of sections for the course
    As an instructor
    I log into a course and click "Edit settings" to modify the number of sections for the course

Scenario: Modify number of sections of a course
    Given I am in a ucla environment
    And a ucla "srs" site exist
    And I log in as "instructor"
    And I go to the default ucla site
    When I follow "Edit settings"
    And I set the following fields to these values:
        | Number of sections | 2 |
    And I press "Save changes"
    Then I should see "Week 1" in the ucla site menu
    And I should see "Week 2" in the ucla site menu
    And I should not see "Week 3" in the ucla site menu
    When I follow "Edit settings"
    And I set the following fields to these values:
        | Number of sections | 3 |
    And I press "Save changes"
    Then I should see "Week 3" in the ucla site menu