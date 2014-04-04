@ucla @mod_elluminate @core_grade @core_edit @CCLE-4465
Feature: Accessing scales
  In order to use scales in my class
  As an instructor
  I need to be able to access the scale page

  Scenario: Accessing scale page
    Given the following "users" exists:
      | username | firstname | lastname | email |
      | teacher | Teacher | 1 | teacher@asd.com |
    Given the following "courses" exists:
      | fullname | shortname |
      | Course 1 | course1   |
    Given the following "course enrolments" exists:
      | user | course | role |
      | teacher | course1 | editingteacher |
    And I log in as "teacher"
    And I follow "Course 1"
    And I follow "Grades"
    When I follow "Scales"
    Then I should see "Course scales"
    When I press "Add a new scale"
    And I fill the moodle form with:
        | Name | Poor-Great |
        | Scale | Poor, Average, Good, Great |
    And I press "Save changes"
    Then I should see "Poor-Great"


