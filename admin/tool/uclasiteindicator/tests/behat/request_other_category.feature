@ucla @core_edit @core_course @tool_uclasiteindicator @CCLE-4341
Feature: "Other" collab sites
  In order to allow flexibility in requesting a collaboration site
  As a requestor
  I want to be able to not have to specify a category when requesting a site

  Scenario: Request and approve a site-request for "Other" category
    Given I am in a ucla environment
    And the following "users" exist:
      | username  | lastname  | firstname |
      | requester | Requester | Course    |
      | mglimited | Manager   | Limited   |
    And the following ucla "roles" exist:
      | role              |
      | editinginstructor |
      | projectlead       |
      | manager_limited   |
    # Note that in PROD we use "Miscellaneous" as the default category, but for
    # Behat tests we are using "Other" since "Miscellaneous" is predefined and
    # has no idnumber.
    And the following "categories" exist:
      | idnumber | name       |
      | CAT1     | Category 1 |
      | OTHER    | Other |
    And the following "role assigns" exist:
      | user      | role            | contextlevel | reference |
      | mglimited | manager_limited | System       |           |
      | mglimited | manager         | Category     | CAT1      |
      | mglimited | manager         | Category     | OTHER |
    And I log in as "admin"
    And I set the following administration settings values:
      | defaultrequestcategory | Other |
    And I log out
    And I log in as "requester"
    And I am on "course/request.php"
    And I set the following fields to these values:
      | indicator_type  | instruction                  |
      | Site category   | Other (provide reason below) |
      | Site full name  | Other category               |
      | Site short name | othercategory                |
      | id_reason       | because                      |
    And I press "Request a collaboration site"
    And I log out
    When I log in as "mglimited"
    And I navigate to "Pending requests" node in "Site administration > Courses"
    Then I should see "Other category"
    And I press "Approve"   
    And I should see "Warning: collaboration site is in the default category for uncategorized sites."
    And I set the following fields to these values:
      | Course category  | Category 1 |
    And I press "Save and display"
    And I navigate to "Edit settings" node in "Course administration"
    And I should not see "Warning: collaboration site is in the default category for uncategorized sites."
    And the field "Course category" matches value "Category 1"