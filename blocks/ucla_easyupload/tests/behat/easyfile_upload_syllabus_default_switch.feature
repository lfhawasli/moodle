@ucla @block_ucla_easyupload @CCLE-4010
Feature: Encourage use of Syllabus tool in Easy File Upload
  As an instructor
  I want to be prompted to use the syllabus tool
  So that students get a consistent way of viewing their syllabi

  Background:
    Given I am in a ucla environment
    And the following "users" exist:
        | username | firstname | lastname |
        | teacher1 | Teacher | 1 |
    And the following ucla "sites" exist:
        | fullname | shortname | type |
        | Course 1 | C1 | srs |
    And the following ucla "enrollments" exist:
        | user | course | role |
        | teacher1 | C1 | editingteacher |

  @javascript
  Scenario: Upload a syllabus using Easy File Upload via Admin panel    
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Admin panel"
    When I follow "Upload a file"
    And I set the field "Name" to "syllabus"
    # Public/private changes to public if syllabus is detected.
    Then the field "Public" matches value "1"
    And I should see "Public -- Anyone viewing the class site can access this. Default has changed!"
    And I should see "If you are uploading a syllabus, please use the Syllabus Upload Tool."