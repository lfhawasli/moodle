<?php

/**
 * Behat UCLA related steps definitions.
 *
 * @package    local_ucla
 * @category   test
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/../../../../lib/tests/behat/behat_data_generators.php');

use Behat\Behat\Context\Step\Given as Given,
    Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Mink\Exception\ExpectationException as ExpectationException,
    Behat\Mink\Exception\DriverException as DriverException,
    Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;
    

class behat_ucla extends behat_base {
    
    /**
     *
     * @var obj local_ucla_generator
     */
    protected $datagenerator;
    
    /**
     *
     * @var obj moodle_course
     */
    protected $course;
        
    // And this term exists: 
    // And I am on the ucla control panel
    
    /**
     * Loads base configs needed for a UCLA environment.  Primarily,
     * this loads the UCLA theme with public/private and a term.
     * 
     * @Given /^I am in a ucla environment$/
     */
    public function load_default_ucla_environment() {
        global $CFG;
        
        // Set the UCLA theme
        set_config('theme', 'uclashared');
        set_config('enablepublicprivate', 1);
        set_config('currentterm', '14W');
        set_config('forcedefaultmymoodle', 1);
        // Add the gradebook report config
        
        // Some settings need to be active immediately.
        $CFG->enablepublicprivate = 1;
        $CFG->forcedefaultmymoodle = 1;
        $CFG->theme = 'uclashared';
    }

    /**
     * @Given /^the following ucla settings exists:$/
     */
    public function load_ucla_config_environment(TableNode $table) {
        throw new PendingException();
    }

    /**
     * Generates a single random UCLA SRS site.  The site will have two enrolled
     * users, a student and an editing instructor.
     * 
     * @Given /^(A|a) ucla srs site exists$/
     */
    public function ucla_srs_site_exists() {
        // Need the ucla class generator.
        require_once(__DIR__ . '/../generator/lib.php');
        $this->datagenerator = new local_ucla_generator();    
        

        // Create a random UCLA SRS site
        $sites = $this->datagenerator->create_class(array());
        $courseid = array_pop($sites)->courseid;
        $this->course = course_get_format($courseid)->get_course();

        // Call Moodle's own generator to create users and enrollments.
        
        // First create users.
        $data = '| username | firstname | lastname | email |
                | editinginstructor | Editing | Instructor | instructor@asd.com |
                | student | Stu | Dent | student1@asd.com |';
        
        $this->getMainContext()->getSubcontext('behat_data_generators')->
                the_following_exists('users', new TableNode($data));
        
        // Now create enrollments
        $data = "| user | course | role |
                | editinginstructor | {$this->course->shortname} | editingteacher |
                | student | {$this->course->shortname} | student |";
        
        $this->getMainContext()->getSubcontext('behat_data_generators')->
                the_following_exists('course enrolments', new TableNode($data));
        
    }

    /**
     * Shortcut to navigate to the default UCLA SRS site.
     * 
     * @Given /^I go to a ucla srs site$/
     */
    public function i_goto_ucla_srs_site() {
        $this->getSession()->visit($this->locate_path('/course/view.php?id=2'));
    }

    /**
     * @Given /^the following ucla srs sites exists:$/
     */
    public function the_following_srs_site_exists(TableNode $table) {
        throw new PendingException();
    }
    
    /**
     * Shortcut definition to log in as "admin".
     * 
     * @Given /^I log in as administrator$/
     */
    public function log_in_as_administrator() {
        return $this->i_login_as_ucla_user('admin');
    }
    
    /**
     * Shortcut definition to log in as "administrator".
     * 
     * @Given /^I log in as instructor/
     */
    public function log_in_as_instructor() {
        return $this->i_login_as_ucla_user('editinginstructor');
    }

    /**
     * Shortcut definition to log in as "student"
     * 
     * @Given /^I log in as student/
     */
    public function log_in_as_student() {
        return $this->i_login_as_ucla_user('student');
    }
    
    /**
     * A log-in step that uses the UCLA special case login page.
     * 
     * @Given /^I log in as ucla "([^"]*)"$/
     */
    public function i_login_as_ucla_user($user) {
        // Use UCLA special case login page.
        $this->getSession()->visit($this->locate_path('/login/ucla_login.php'));
        
        return array(
            new Given('I fill in "' . get_string('username') . '" with "' . $user . '"'),
            new Given('I fill in "' . get_string('password') . '" with "' . $user . '"'),
            new Given('I press "' . get_string('login') . '"')
        );
    }


    /**
     * Pauses the scenario until the user presses a key. 
     * Useful when debugging a scenario. 
     * 
     * @Then /^(?:|I )put a breakpoint$/ 
     */ 
    public function i_put_a_breakpoint() {
        
        fwrite(STDOUT, "\033[s \033[93m[Breakpoint] Press \033[1;93m[RETURN]\033[0;93m to continue...\033[0m"); 
        
        while (fgets(STDIN, 1024) == '') {
            // Intentionally empty.
        } 
        
        fwrite(STDOUT, "\033[u");
        return; 
        
    }


}