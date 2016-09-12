@ucla @local_ucla @core_edit @CCLE-6256
Feature: Additional names field hidden in Edit profile
  In order to see that Additional names field doesn't appear in Edit profile
  As any user
  I need to go to "Edit profile", and see if the Additional Names field doesn't appear only if using UCLA theme

Scenario: Additional names is hidden in UCLA environment
    Given I am in a ucla environment
    And I log in as "admin"
    And I follow "Profile" in the user menu
    When I follow "Edit profile"
    Then I should not see "Additional names"

Scenario: Additional names is not hidden in other themes
    Given I log in as "admin"
    And I follow "Profile" in the user menu
    When I follow "Edit profile"
    Then I should see "Additional names"
  
