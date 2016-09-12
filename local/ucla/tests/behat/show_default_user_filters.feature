@core_user @ucla @local_ucla @core_edit @CCLE-3783
Feature: Add email address and id number to default user filters
  In order to more easily filter through students
  As a user
  I need to be able to see the 'Email address' and 'ID number' filters as soon as I open the 'Browse list of users' page

  Background:
    Given I am in a ucla environment
    And I log in as "admin"
    And I expand "Site administration" node
    And I expand "Users" node
    And I expand "Accounts" node

  @javascript
  Scenario: Go to 'Browse list of users' page
    When I follow "Browse list of users"
    Then "email" "field" should be visible
    And "idnumber" "field" should be visible