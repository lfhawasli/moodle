@ucla @format_ucla
Feature: JIT links
   In order to gain quick access to the Admin panel tools
   As an instructor
   I want to be able to access those tools in a section
  
Scenario: Check JIT links
   Given I am in a ucla environment
   And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
   And the following ucla "sites" exist:
      | fullname | shortname | type |
      | Test course 1 | C1 | srs |
   And the following ucla "enrollments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
   And I log in as "teacher1"
   And I follow "Test course 1"
   And I turn editing mode on
   When I follow the "Week 2" section in the ucla site menu
   And I follow "Upload file"
   # Check if the add to section drop down box selects Week 2 by default.
   Then I should see "Week 2" in the "//*[@id='id_section']/*[@selected and text()='Week 2']" "xpath_element"
   When I follow the "Week 3" section in the ucla site menu
   And I follow "Add link"
   Then I should see "Week 3" in the "//*[@id='id_section']/*[@selected and text()='Week 3']" "xpath_element"
   When I follow the "Week 4" section in the ucla site menu
   And I follow "Add text"
   Then I should see "Week 4" in the "//*[@id='id_section']/*[@selected and text()='Week 4']" "xpath_element"
   When I follow the "Week 5" section in the ucla site menu
   And I follow "Add subheading"
   Then I should see "Week 5" in the "//*[@id='id_section']/*[@selected and text()='Week 5']" "xpath_element"
