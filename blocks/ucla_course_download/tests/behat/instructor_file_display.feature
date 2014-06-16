@ucla @block_ucla_course_download @test
Feature: Show files that may be included in zip
  As a instructor
  I want to see what files are available for download
  In order I can see what files students are downloading.

  Background:
    Given I am in a ucla environment
    And the following "users" exists:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
      | student1 | Student | 1 | student1@asd.com |
    And the following ucla "sites" exists:
        | fullname | shortname | type |
        | course 1 | C1 | srs |
    And the following ucla "enrollments" exists:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |

  @javascript
  Scenario: Adding multiple files into sections. hidding individual files and sections, then verifying instructor can see the visibility markers.
    And I log in as ucla "teacher1"
    And I browse to site "C1"
    And I turn editing mode on
    And I follow the "Week 1" section in the ucla site menu
    And I upload the "blocks/ucla_course_download/tests/fixtures/smallfile.file" file as "Small file" to section "1"
    And I upload the "blocks/ucla_course_download/tests/fixtures/mediumfile.file" file as "Medium file" to section "1"
    And I click on "Hide" "link" in the "Medium" activity
    And I follow the "Week 2" section in the ucla site menu
    And I upload the "blocks/ucla_course_download/tests/fixtures/largefile.file" file as "Large file" to section "2"
    And I follow the "Week 3" section in the ucla site menu
    And I upload the "lib/tests/fixtures/empty.txt" file as "Empty file" to section "3"
    And I hide section "3"
    And I press "Control Panel"
    And I follow "Download course content"
    # Small file should be marked visible 
    And I should see "Small file9.8KB" in the ".zip-contents ul:nth-of-type(1) li" "css_element"
    # Medium file should be marked hidden
    And I should see "Medium file97.7KB" in the ".zip-contents ul:nth-of-type(1) li.omitted" "css_element"
    # Large file should be marked visible
    And I should see "Large file1.4MB" in the ".zip-contents ul:nth-of-type(2) li" "css_element"
    # Empty file should be marked hidden since it's in a hidden section.
    And I should see "Empty file32 bytes" in the ".zip-contents ul:nth-of-type(3) li.omitted" "css_element"
    # Changing the download max value should hide values under that size.
    And I set the course download max limit to "1" MB
    And I reload the page
    And I should see "Files over 1MB will be excluded"
    # Large file should be hidden now
    And I should see "Large file1.4MB" in the ".zip-contents ul:nth-of-type(2) li.omitted" "css_element"
    
    # Check that student cannot view file list.
    And I log out
    And I log in as ucla "student1"
    And I browse to site "C1"
    And I press "Control Panel"
    And I follow "Download course content"
    And I should not see "Files included are ones that are already visible to students on your site." 