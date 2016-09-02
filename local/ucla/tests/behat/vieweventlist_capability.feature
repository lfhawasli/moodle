@ucla @local_ucla @report @report_eventlist @core_edit @CCLE-4671
Feature: Capability to view Events list page

  @javascript
  Scenario: Event list page is viewable only with the proper permissions
    Given the following "users" exist:
        | username    |
        | testmanager |
    Given the following "role assigns" exist:
        | user        | role    | contextlevel | reference |
        | testmanager | manager | System       |           |
    And I log in as "testmanager"
    And I expand "Site administration" node
    And I expand "Reports" node
    Then I should see "Events list"
    And I navigate to "Define roles" node in "Site administration > Users > Permissions"
    And I follow "Manager"
    And I press "Edit"
    And I set the field "local/ucla:vieweventlist" to "0"
    And I press "Save changes"
    And I expand "Reports" node
    Then I should not see "Events list"