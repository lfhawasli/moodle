@ucla @local_ucla @core_user @core_edit @CCLE-4874
Feature: Freeze "Additional names" field
    In order to accept preferred names from the Registrar
    As a user
    I need to not be able to modify the "Additional names" fields myself

@javascript
Scenario: Checking that "Additional names" are non-editable.
    Given I am in a ucla environment
    And  the following "users" exist:
        | username | firstname | lastname | email           |
        | student  | Student   | 1        | student@asd.com |
    And I log in as "student"
    And I follow "Profile" in the user menu
    And I follow "Edit profile"
    And I expand all fieldsets
    Then the "firstnamephonetic" "field" should be readonly
    And the "lastnamephonetic" "field" should be readonly
    And the "middlename" "field" should be readonly
    And the "alternatename" "field" should be readonly
