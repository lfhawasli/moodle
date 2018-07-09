@ucla @core_edit @core_course @tool_uclasiteindicator @CCLE-6002
Feature: Changing site type
      
  Background:
    Given the following ucla "sites" exist:
        | fullname | shortname | type    |
        | Course 1 | C1        | private |
    And the following "users" exist:
        | username          | lastname | firstname |
        | course_editor     | User     | User      |
    And the following "role assigns" exist:
        | user          | role    | contextlevel | reference |
        | course_editor | manager | System       |           |
    And the following "course enrolments" exist:
        | user          | course  | role    |
        | course_editor | C1      | manager |
    And I log in as "course_editor" 
    And I am on "Course 1" course homepage
    And I navigate to "Edit settings" node in "Course administration"

  Scenario: Changing site type from Private to Instruction (non-degree-related)
    Given I should see "Private" in the "fitem_id_indicator" "region"
    When I set the following fields to these values:
        | id_indicator_change_instruction_noniei | 1 |
    And I press "Save and display"
    And I navigate to "Edit settings" node in "Course administration"
    Then I should see "Instruction (non-degree-related)" in the "fitem_id_indicator" "region"
    