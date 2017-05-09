@ucla @tool_uclasupportconsole @CCLE-4533
Feature: Reopened Class Report
  In order to find which past courses were reopened
  As an administrator
  I should be able to run a report listing reopened classes.
  
  @javascript
  Scenario: Identify reopened classes
    Given I am in a ucla environment
    And the following ucla "sites" exist:
        | fullname | shortname | term | type |
        | course 1 | C1        | 12W  | srs  |
    And I log in as "admin"
    And I navigate to "UCLA support console" node in "Site administration > Reports"
    And I set the field "showreopenedclasses_term_selector" to "12W"
    When I click on "Go" "button" in the "#showreopenedclasses" "css_element"
    Then I should see "There is 1 result for input [12W]"
    # Now hide site.
    And I browse to site "C1"
    And I follow "Edit settings"
    And I set the field "visible" to "Hide"
    And I press "Save and display"
    And I navigate to "UCLA support console" node in "Site administration > Reports"
    When I set the field "showreopenedclasses_term_selector" to "12W"
    And I click on "Go" "button" in the "#showreopenedclasses" "css_element"
    Then I should see "There are no results for input"