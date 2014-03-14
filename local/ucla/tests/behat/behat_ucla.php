<?php
// This file is part of the UCLA local plugin for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Behat UCLA related steps definitions.
 *
 * @package    local_ucla
 * @category   test
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// NOTE: No MOODLE_INTERNAL test here, this file may be required by behat before
// including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/../../../../lib/tests/behat/behat_data_generators.php');

use Behat\Behat\Context\Step\Given as Given,
    Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Mink\Exception\ExpectationException as ExpectationException,
    Behat\Mink\Exception\DriverException as DriverException,
    Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;

/**
 * Behat custom steps.
 *
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_ucla extends behat_base {

    /**
     * UCLA data generator.
     * @var local_ucla_generator
     */
    protected static $generator = null;

    /**
     * Array of UCLA sites (course objs) generated.
     * @var array
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

        // Set the UCLA theme.
        set_config('theme', 'uclashared');
        // Set public/private.
        set_config('enablepublicprivate', 1);
        set_config('enablegroupmembersonly', true);
        // Set term.
        set_config('currentterm', '14W');
        set_config('forcedefaultmymoodle', 1);
        // Additional config variables
        set_config('grader_report_grouping_filter', 1);
        set_config('collapsesubmissionstatus', 1, 'local_ucla');
        
        // Some settings need to be active immediately.
        $CFG->enablepublicprivate = 1;
        $CFG->enablegroupmembersonly = true;
        $CFG->forcedefaultmymoodle = 1;
        $CFG->theme = 'uclashared';
    }

    /**
     * Step to generate UCLA SRS + collab sites, and enrolments.
     *
     * NOTE: If you are creating activities, make sure to also set the idnumber
     * field.
     *
     * @Given /^the following ucla "([^"]*)" exists:$/
     *
     * @param string $elementname
     * @param TableNode $data
     */
    public function the_following_exists($elementname, TableNode $data) {
        global $DB;

        switch ($elementname) {
            case 'sites':
                $this->generate_ucla_sites($data);
                break;

            case 'enrollments':
            case 'enrolments':
            case 'course enrolments':

                if (empty($this->courses)) {
                    throw new ExpectationException('There are no UCLA sites generated',
                    $this->getSession());
                }

                // Make sure that the proper UCLA roles exists.
                $this->get_data_generator()->create_ucla_roles();

                // Need to set proper course shortname so that Moodle's generator
                // knows what to reference.  In this case, I have to regenerate the
                // table as text because I can't modify the TableNode obj directly.
                $table = "| user | course | role |";

                foreach ($data->getHash() as $elementdata) {
                    $table .= "\n| {$elementdata['user']} | " .
                            "{$this->courses[$elementdata['course']]->shortname} | " .
                            "{$elementdata['role']} |";
                }

                // Forward the work to Moodle's data generators.
                $this->getMainContext()->getSubcontext('behat_data_generators')
                        ->the_following_exists('course enrolments',
                                new TableNode($table));

                break;
            case 'activities':
                require_once(__DIR__ . '/../../../../local/publicprivate/lib/module.class.php');
                $this->getMainContext()->getSubcontext('behat_data_generators')
                        ->the_following_exists('activities', $data);
                // Make each activity either public or private (default).
                foreach ($data->getHash() as $elementdata) {
                    if (!empty($elementdata['private'])) {
                        // Find course module.
                        $cmid = $DB->get_field('course_modules', 'id',
                                array('idnumber' => $elementdata['idnumber']));
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
     *
     * @param string $shortname
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

        $datagenerator = self::get_data_generator();

        foreach ($data->getHash() as $elementdata) {

            $sitetype = $elementdata['type'];

            // Need to have a site type.
            if (empty($sitetype)) {
                throw new ExpectationException('A site type was not specified',
                $this->getSession());
            }

            switch ($sitetype) {
                case 'class':
                case 'srs':
                    // Create a random UCLA SRS site.
                    $class = $datagenerator->create_class(array());
                    $courseid = array_pop($class)->courseid;

                    // Save site.
                    $this->courses[$elementdata['shortname']] = course_get_format($courseid)->get_course();
                    break;

                case 'instruction':
                case 'instruction_noniei':
                case 'non_instruction':
                case 'research':
                case 'private':
                case 'test':
                    $elementdata['type'] = $sitetype;
                    $course = $datagenerator->create_collab($elementdata);
                    $this->courses[$elementdata['shortname']] = $course;
                    break;
                default:
                    throw new ExpectationException('The site type specified does not exist',
                    $this->getSession());
            }
        }
    }

    /**
     * Generates a single UCLA site.  The site will have two enrolled
     * users, a student and an editing instructor.
     * 
     * @Given /^A ucla "([^"]*)" site exists$/
     * 
     * @param string $site type for a collab site, or 'class' for an SRS site
     */
    public function ucla_site_exists($site) {
        global $DB;

        $data = "| type |
                 | $site |";
        $this->generate_ucla_sites(new TableNode($data));

        // Call Moodle's own generator to create users and enrollments.
        // First create users.
        $data = '| username | firstname | lastname | email |
                | instructor | Editing | Instructor | instructor@asd.com |
                | student | Stu | Dent | student1@asd.com |';

        $this->getMainContext()->getSubcontext('behat_data_generators')
                ->the_following_exists('users', new TableNode($data));

        // Now create enrollments
        $course = reset($this->courses);    // Use newly created course above.
        $data = "| user | course | role |
                | instructor | {$course->shortname} | editinginstructor |
                | student | {$course->shortname} | student |";

        $this->getMainContext()->getSubcontext('behat_data_generators')
                ->the_following_exists('course enrolments', new TableNode($data));
    }

    /**
     * Generates a single random UCLA SRS site.  The site will have two enrolled
     * users, a student and an editing instructor.
     * 
     * @Given /^(A|a) ucla srs site exists$/
     */
    public function ucla_srs_site_exists() {
        $this->ucla_site_exists('class');
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
     *
     * @return array
     */
    public function log_in_as_administrator() {
        return $this->i_login_as_ucla_user('admin');
    }

    /**
     * Shortcut definition to log in as "instructor".
     *
     * @Given /^I log in as instructor/
     *
     * @return array
     */
    public function log_in_as_instructor() {
        return $this->i_login_as_ucla_user('instructor');
    }

    /**
     * Shortcut definition to log in as "student"
     * 
     * @Given /^I log in as student/
     *
     * @return array
     */
    public function log_in_as_student() {
        return $this->i_login_as_ucla_user('student');
    }

    /**
     * A log-in step that uses the UCLA special case login page.
     * 
     * @Given /^I log in as ucla "([^"]*)"$/
     *
     * @param string $user
     * @return array
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

        fwrite(STDOUT,
                "\033[s \033[93m[Breakpoint] Press \033[1;93m[RETURN]\033[0;93m to continue...\033[0m");

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

        if (!$id = $DB->get_field('course', 'id',
                array('shortname' => $shortname))) {
            throw new Exception('The specified course with shortname"' . $shortname . '" does not exist');
        }
        return $id;
    }

    /**
     * Get ucla data generator
     * @static
     * @return testing_data_generator
     */
    public static function get_data_generator() {
        if (is_null(self::$generator)) {
            require_once(__DIR__ . '/../generator/lib.php');
            self::$generator = new local_ucla_generator();
        }
        return self::$generator;
    }

}
