@ucla @tool_uclasupportconsole @CCLE-4533
Feature: Reopened Class Report
  In order to filter classes and debug more easily
  As an administrator
  I should be able to identify reopened classes.
  
  @javascript
  Scenario: Identify reopened classes
    Given I am in a ucla environment
    And the following ucla "sites" exist:
        | fullname | shortname | term | type |
        | course 1 | C1        | 12W  | srs  |
    And the following ucla "sites" exist:
        | fullname | shortname | type |
        | course 2 | C2        | srs  |
    And the following "users" exist:
        | username | firstname | lastname | email           | idnumber |
        | teacher  | Teacher   | 1        | teacher@asd.com | 1234     |
    And the following ucla "enrollments" exist:
        | user    | course   | role              |
        | teacher | C1       | editinginstructor |
        | teacher | C2       | editinginstructor |
    And I log in as "admin"
    And I browse to site "C1"
    And I follow "Edit settings"
    And I set the field "visible" to "Show"
    And I press "Save changes"
    And I browse to site "C2"
    And I follow "Edit settings"
    And I set the field "visible" to "Show"
    And I press "Save changes"
    And I follow "Courses"
    And I expand "Site administration" node
    And I expand "Plugins" node
    And I expand "Reports" node
    And I expand "Reports" node
    And I follow "UCLA support console"
    And I set the field "showreopenedclasses_term_selector" to "12W"
    When I click on "Go" "button" in the "#showreopenedclasses" "css_element"
    Then I should see "There is 1 result for input [12W]"
    Then I should see "Teacher"
    And I browse to site "C1"
    And I follow "Edit settings"
    And I set the field "visible" to "Hide"
    And I press "Save changes"
    And I follow "Courses"
    And I expand "Site administration" node
    And I expand "Plugins" node
    And I expand "Reports" node
    And I expand "Reports" node
    And I follow "UCLA support console"
    When I click on "Go" "button" in the "#showreopenedclasses" "css_element"
    Then I should see "There are no results for input"
    And I follow "UCLA support console"
    When I click on "Go" "button" in the "#showreopenedclasses" "css_element"
    Then I should see "There are no results for input"