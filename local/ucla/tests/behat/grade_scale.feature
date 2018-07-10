@ucla @local_ucla @mod_elluminate @core_grades @core_edit @CCLE-4465
Feature: Accessing scales
  In order to use scales in my class
  As an instructor
  I need to be able to access the scale page

  Scenario: Accessing scale page
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher | Teacher | 1 | teacher@asd.com |
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | course1   |
    Given the following "course enrolments" exist:
      | user | course | role |
      | teacher | course1 | editingteacher |
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I navigate to "Grades" node in "Course administration"
    And I navigate to "Scales" node in "Grade administration"
    Then I should see "Course scales"
    When I press "Add a new scale"
    And I set the following fields to these values:
        | Name | Poor-Great |
        | Scale | Poor, Average, Good, Great |
        | Description | Description |
    And I press "Save changes"
    Then I should see "Poor-Great"
