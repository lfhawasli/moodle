@ucla @local_ucla @core_form @CCLE-6930 @CCLE-5791 @javascript
Feature: Expand all sections within shortform
	When 'Expand all' is clicked
	I would like all subsections to expand display 'Show less...' instead of 'Show more...'
	So that 'Expand all' does its named function

Background:
	Given I am in a ucla environment
	And the following "courses" exist:
		| fullname | shortname | category | groupmode |
		| Course 1 | C1 | 0 | 1 |
	And the following "users" exist:
		| username | firstname | lastname | email | alternatename |
		| teacher | Teacher | T1 | teacher1@asd.com | t1 |
	And the following "course enrolments" exist:
		| user | course | role |
		| teacher | C1 | editingteacher	|

Scenario Outline: Hit expand all button.
	Given I log in as "teacher"
	And I follow "Course 1"
	And I turn editing mode on
	And I add a "<Activity>" to section "1"
	And I press "Expand all"
	Then I should not see "Show more..."
       And I should see "Show less..."
	Examples:
		| Activity |
		| Quiz |
               | External tool |
               | HotPot |
               | Lesson |
               | SCORM package |