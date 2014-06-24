@ucla @block_ucla_control_panel @jitlink
Feature: JIT links
  As an instructor
  When I follow a JIT link
  I should be brought into the appropriate easy upload tool
  And the default section that is selected in the easy upload tool is the section for which I clicked on the link for
  
 Background:
    Given I am in a ucla environment
    And the following "users" exists:
    | username | firstname | lastname | email |
    | teacher1 | Teacher | 1 | teacher1@asd.com |
    And the following ucla "sites" exists:
    | fullname | shortname | type |
    | course 1 | C1 | srs |
    And the following ucla "enrollments" exists:
    | user | course | role |
    | teacher1 | C1 | editingteacher |

 @javascript
  Scenario: Check JIT links  
    And I log in as ucla "teacher1"
    And I follow "Test course 1"
    And I turn editing mode on
    And I follow the "Week 2" section in the ucla site menu
    And I follow "Upload file"
    #check if the add to section drop down box selects Week 2 by default
    Then I should see "Week 2" in the "//*[@id='id_section']/*[@selected and text()='Week 2']" "xpath_element" 
    And I follow the "Week 3" section in the ucla site menu
    And I follow "Add link"
    Then I should see "Week 3" in the "//*[@id='id_section']/*[@selected and text()='Week 3']" "xpath_element" 
    And I follow the "Week 4" section in the ucla site menu
    And I follow "Add text"
    Then I should see "Week 4" in the "//*[@id='id_section']/*[@selected and text()='Week 4']" "xpath_element" 
    And I follow the "Week 5" section in the ucla site menu
    And I follow "Add subheading"
    Then I should see "Week 5" in the "//*[@id='id_section']/*[@selected and text()='Week 5']" "xpath_element" 
