@ucla @block_ucla_copyright_status
Feature: Assign copyright status.
    As an instructor
    I want to be able to assign the copyright status to the file I uploaded.

Background:
    Given I am in a ucla environment
    And the following "users" exist:
    | username | firstname | lastname | email |
    | teacher1 | Teacher | 1 | teacher1@abc.com |
    And the following ucla "sites" exists:
    | fullname | shortname | type | numsections |
    | course 1 | C1 | srs | 3 |
    And the following ucla "enrollments" exists:
    | user | course | role |
    | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I browse to site "C1"
    And I turn editing mode on
    And I follow "Week 1"

@javascript
Scenario: Copyright Status: "I own the copyright" works
    Given I add a "File" to section "1" and I fill the form with:
      | Name | Test file with I own copyright status |
      | Description | I will assign I own copyright to this file |
    And I upload "lib/tests/fixtures/empty.txt" file to "Select files" filemanager
    And I press "Save and return to course"
    And I follow "Manage copyright"
    And I wait "2" seconds
    And I set the field "filter_copyright" to "All"
    And I wait "2" seconds
    Then I should see "Test file with I own copyright status"
    And I click on "I own the copyright" "option" in the "//select[contains(@name,'filecopyright_')]" "xpath_element"
    And I press "Save changes"
    And "//option[@value='iown' and @selected='selected']" "xpath_element" should exist

@javascript
Scenario: Copyright Status: "The UC Regents own the copyright" works
    Given I add a "File" to section "1" and I fill the form with:
      | Name | Test file with The UC Regents own the copyright status |
      | Description | I will assign The UC Regents own the copyright to this file |
    And I upload "lib/tests/fixtures/empty.txt" file to "Select files" filemanager
    And I press "Save and return to course"
    And I follow "Manage copyright"
    And I wait "2" seconds
    And I set the field "filter_copyright" to "All"
    And I wait "2" seconds
    Then I should see "Test file with The UC Regents own the copyright status"
    And I click on "The UC Regents own the copyright" "option" in the "//select[contains(@name,'filecopyright_')]" "xpath_element"
    And I press "Save changes"
    And "//option[@value='ucown' and @selected='selected']" "xpath_element" should exist

@javascript
Scenario: Copyright Status: "Item is licensed by the UCLA Library" works
    Given I add a "File" to section "1" and I fill the form with:
      | Name | Test file with Item is licensed by the UCLA Library status |
      | Description | I will assign Item is licensed by the UCLA Library to this file |
    And I upload "lib/tests/fixtures/empty.txt" file to "Select files" filemanager
    And I press "Save and return to course"
    And I follow "Manage copyright"
    And I wait "2" seconds
    And I set the field "filter_copyright" to "All"
    And I wait "2" seconds
    Then I should see "Test file with Item is licensed by the UCLA Library status"
    And I click on "Item is licensed by the UCLA Library" "option" in the "//select[contains(@name,'filecopyright_')]" "xpath_element"
    And I press "Save changes"
    And "//option[@value='lib' and @selected='selected']" "xpath_element" should exist
  
@javascript
Scenario: Copyright Status: "Item is in the public domain" works
    Given I add a "File" to section "1" and I fill the form with:
      | Name | Test file with Item is in the public domain status |
      | Description | I will assign Item is in the public domain to this file |
    And I upload "lib/tests/fixtures/empty.txt" file to "Select files" filemanager
    And I press "Save and return to course"
    And I follow "Manage copyright"
    And I wait "2" seconds
    And I set the field "filter_copyright" to "All"
    And I wait "2" seconds
    Then I should see "Test file with Item is in the public domain status"
    And I click on "Item is in the public domain" "option" in the "//select[contains(@name,'filecopyright_')]" "xpath_element"
    And I press "Save changes"
    And "//option[@value='public1' and @selected='selected']" "xpath_element" should exist

@javascript
Scenario: Copyright Status: "Item is available for this use via Creative Commons license" works
    Given I add a "File" to section "1" and I fill the form with:
      | Name | Test file with Item is available for this use via Creative Commons license status |
      | Description | I will assign Item is available for this use via Creative Commons license to this file |
    And I upload "lib/tests/fixtures/empty.txt" file to "Select files" filemanager
    And I press "Save and return to course"
    And I follow "Manage copyright"
    And I wait "2" seconds
    And I set the field "filter_copyright" to "All"
    And I wait "2" seconds
    Then I should see "Test file with Item is available for this use via Creative Commons license status"
    And I click on "Item is available for this use via Creative Commons license" "option" in the "//select[contains(@name,'filecopyright_')]" "xpath_element"
    And I press "Save changes"
    And "//option[@value='cc1' and @selected='selected']" "xpath_element" should exist

@javascript
Scenario: Copyright Status: "I have obtained written permission from the copyright holder" works
    Given I add a "File" to section "1" and I fill the form with:
      | Name | Test file with I have obtained written permission from the copyright holder status |
      | Description | I will assign I have obtained written permission from the copyright holder to this file |
    And I upload "lib/tests/fixtures/empty.txt" file to "Select files" filemanager
    And I press "Save and return to course"
    And I follow "Manage copyright"
    And I wait "2" seconds
    And I set the field "filter_copyright" to "All"
    And I wait "2" seconds
    Then I should see "Test file with I have obtained written permission from the copyright holder status"
    And I click on "I have obtained written permission from the copyright holder" "option" in the "//select[contains(@name,'filecopyright_')]" "xpath_element"
    And I press "Save changes"
    And "//option[@value='obtained' and @selected='selected']" "xpath_element" should exist

@javascript
Scenario: Copyright Status: "I am using this item under fair use" works
    Given I add a "File" to section "1" and I fill the form with:
      | Name | Test file with I am using this item under fair use status |
      | Description | I will assign I am using this item under fair use to this file |
    And I upload "lib/tests/fixtures/empty.txt" file to "Select files" filemanager
    And I press "Save and return to course"
    And I follow "Manage copyright"
    And I wait "2" seconds
    And I set the field "filter_copyright" to "All"
    And I wait "2" seconds
    Then I should see "Test file with I am using this item under fair use status"
    And I click on "I am using this item under fair use" "option" in the "//select[contains(@name,'filecopyright_')]" "xpath_element"
    And I press "Save changes"
    And "//option[@value='fairuse' and @selected='selected']" "xpath_element" should exist

@javascript
Scenario: Copyright Status: "Copyright status not yet identified" works
    Given I add a "File" to section "1" and I fill the form with:
      | Name | Test file with Copyright status not yet identified status |
      | Description | I will assign Copyright status not yet identified to this file |
    And I upload "lib/tests/fixtures/empty.txt" file to "Select files" filemanager
    And I press "Save and return to course"
    And I follow "Manage copyright"
    And I wait "2" seconds
    And I set the field "filter_copyright" to "All"
    And I wait "2" seconds
    Then I should see "Test file with Copyright status not yet identified status"
    And I click on "Copyright status not yet identified" "option" in the "//select[contains(@name,'filecopyright_')]" "xpath_element"
    And I press "Save changes"
    And "//option[@value='tbd' and @selected='selected']" "xpath_element" should exist