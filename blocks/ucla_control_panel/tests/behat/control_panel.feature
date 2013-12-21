@ucla @block_ucla_control_panel
Feature: Control Panel
  As an instructor
  I want to see the control panel button
  So that I can access its tools

  Background:
    Given I am in a ucla environment
    And a ucla srs site exists

  @javascript
  Scenario: Control Panel visiblity    
    And I log in as instructor
    And I go to a ucla srs site
    When I press "Control Panel"
    Then I should see "Control panel"
    And I should see "Most commonly used"