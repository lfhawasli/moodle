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
    public $course;
        
    /**
     *
     * @var array of UCLA sites (course objs) generated
     */
    public $courses = array();

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
        // Set public/private
        set_config('enablepublicprivate', 1);
        set_config('enablegroupmembersonly', true);
        // Set term
        set_config('currentterm', '14W');
        set_config('forcedefaultmymoodle', 1);
        set_config('grader_report_grouping_filter', 1);
        // Add the gradebook report config
        
        // Some settings need to be active immediately.
        $CFG->enablepublicprivate = 1;
        $CFG->enablegroupmembersonly = true;
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
     * Step to generate UCLA SRS + collab sites, and enrolments.
     *
     * NOTE: If you are creating activities, make sure to also set the idnumber
     * field.
     *
     * @Given /^the following ucla "([^"]*)" exists:$/
     */
    public function the_following_exists($elementname, TableNode $data) {
        global $DB;
        // Need the ucla class generator.
        require_once(__DIR__ . '/../generator/lib.php');
        $this->datagenerator = new local_ucla_generator();
        
        switch ($elementname) {
            case 'sites':
                $this->generate_ucla_sites($data);
                break;
            
            case 'enrollments':
            case 'enrolments':
            case 'course enrolments':
                
                if (empty($this->courses)) {
                    throw new ExpectationException('There are no UCLA sites generated', $this->getSession());
                }
                
                // Need to set proper course shortname so that Moodle's generator
                // knows what to reference.  In this case, I have to regenerate the
                // table as text because I can't modify the TableNode obj directly.
                $table = "| user | course | role |";
                
                foreach ($data->getHash() as $elementdata) { 
                    $table .= "\n| {$elementdata['user']} | {$this->courses[$elementdata['course']]->shortname} | {$elementdata['role']} |";
                }

                // Forward the work to Moodle's data generators
                $this->getMainContext()->getSubcontext('behat_data_generators')->
                    the_following_exists('course enrolments', new TableNode($table));
                
                // Update role to ucla role, otherwise Office Hours won't show up
                // @todo: need to generate real UCLA roles
                $record = $DB->get_record('role', array('shortname' => 'editingteacher'));
                $record->shortname = 'editinginstructor';
                $DB->update_record('role', $record);

                break;
            case 'activities':
                require_once(__DIR__ . '/../../../../local/publicprivate/lib/module.class.php');
                $this->getMainContext()->getSubcontext('behat_data_generators')->
                    the_following_exists('activities', $data);
                // Make each activity either public or private (default).
                foreach ($data->getHash() as $elementdata) {
                    if (!empty($elementdata['private'])) {
                        // Find course module.
                        $cmid = $DB->get_field('course_modules', 'id', array('idnumber' => $elementdata['idnumber']));
                        $ppmod = PublicPrivate_Module::build($cmid);
                        $ppmod->enable();
                    }
                }
        }
    }
    
    /**
     * Step to browse directly to a site with a given shortname.
     * 
     * @Given /^I browse to site "([^"]*)"$/
     */
    public function i_browse_to_site($shortname) {
        $courseid = $this->courses[$shortname]->id;
        $this->getSession()->visit($this->locate_path('/course/view.php?id=' . $courseid));
    }

    
    /**
     * Generates UCLA SRS and collab sites and saves course objs in $courses array.
     * 
     * @param \Behat\Gherkin\Node\TableNode $data
     * @throws PendingException
     */
    protected function generate_ucla_sites(TableNode $data) {
        
        foreach ($data->getHash() as $elementdata) {

            $sitetype = $elementdata['type'];
            
            // Need to have a site type.
            if (empty($sitetype)) {
                throw new ExpectationException('A site type was not specified', $this->getSession());
            }
            
            switch ($sitetype) {
                case 'class':
                case 'srs':
                    // Create a random UCLA SRS site
                    $class = $this->datagenerator->create_class(array());
                    $courseid = array_pop($class)->courseid;
                    
                    // Save site
                    $this->courses[$elementdata['shortname']] = course_get_format($courseid)->get_course();
                    break;

                case 'instruction':
                case 'instruction_noniei':
                case 'non_instruction':
                case 'research':
                case 'private':
                case 'test':
                    $elementdata['type'] = $sitetype;
                    $course = $this->datagenerator->create_collab($elementdata);
                    $this->courses[$elementdata['shortname']] = $course;
                    break;
                default:
                    throw new ExpectationException('The site type specified does not exist', $this->getSession());
            }
        }
    }
    
    /**
     * Creates a collab site indicator entry.
     * 
     * @todo use actual site_indicator class.
     * 
     * @global type $DB
     * @param type $courseid
     * @param type $type
     */
    protected function create_collab_site($courseid, $type) {
        global $DB;
        
        $indicator = new stdClass();
        $indicator->courseid = $courseid;
        $indicator->type = $type;

        $DB->insert_record('ucla_siteindicator', $indicator);
    }


    /**
     * Generates a single a UCLA site.  The site will have two enrolled
     * users, a student and an editing instructor.
     * 
     * @Given /^A ucla "([^"]*)" site exists$/
     * 
     * @param string $site type for a collab site, or 'class' for an SRS site
     */
    public function ucla_site_exists($site) {
        global $DB;
        
        // Need the ucla class generator.
        require_once(__DIR__ . '/../generator/lib.php');
        $this->datagenerator = new local_ucla_generator();
        
        switch ($site) {
            case 'class':
                // Create a random UCLA SRS site
                $class = $this->datagenerator->create_class(array());
                $courseid = array_pop($class)->courseid;
                $this->course = course_get_format($courseid)->get_course();
                break;
            
            case 'instruction':
            case 'instruction_noniei':
            case 'non_instruction':
            case 'research':
            case 'private':
            case 'test':
                
                $title = ucfirst($site) . ' collab site';
                $data = "| fullname | shortname | format | numsections |
                         | $title | $site | ucla | 10 |";
                $this->getMainContext()->getSubcontext('behat_data_generators')->
                    the_following_exists('courses', new TableNode($data));
                
                $courseid = $this->get_course_id($site);
                $this->course = course_get_format($courseid)->get_course();
                
                // Make official collab site
                $indicator = new stdClass();
                $indicator->courseid = $courseid;
                $indicator->type = $site;
                // @todo: use actual site_indicator class
                $DB->insert_record('ucla_siteindicator', $indicator);
                
                break;
            default:
                throw new PendingException();
        }
        
        // Call Moodle's own generator to create users and enrollments.
        
        // First create users.
        $data = '| username | firstname | lastname | email |
                | instructor | Editing | Instructor | instructor@asd.com |
                | student | Stu | Dent | student1@asd.com |';
        
        $this->getMainContext()->getSubcontext('behat_data_generators')->
                the_following_exists('users', new TableNode($data));
        
        // Now create enrollments
        $data = "| user | course | role |
                | instructor | {$this->course->shortname} | editingteacher |
                | student | {$this->course->shortname} | student |";
        
        $this->getMainContext()->getSubcontext('behat_data_generators')->
                the_following_exists('course enrolments', new TableNode($data));
        
        
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
                | instructor | Editing | Instructor | instructor@asd.com |
                | student | Stu | Dent | student1@asd.com |';
        
        $this->getMainContext()->getSubcontext('behat_data_generators')->
                the_following_exists('users', new TableNode($data));
        
        // Now create enrollments
        $data = "| user | course | role |
                | instructor | {$this->course->shortname} | editingteacher |
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
        return $this->i_login_as_ucla_user('instructor');
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

    /**
     * Gets the course id from it's shortname.
     * @throws Exception
     * @param string $shortname
     * @return int
     */
    protected function get_course_id($shortname) {
        global $DB;

        if (!$id = $DB->get_field('course', 'id', array('shortname' => $shortname))) {
            throw new Exception('The specified course with shortname"' . $shortname . '" does not exist');
        }
        return $id;
    }


}
