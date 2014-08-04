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

require_once(__DIR__ . '/../../../../lib/behat/behat_files.php');
require_once(__DIR__ . '/../../../../lib/tests/behat/behat_data_generators.php');

use Behat\Behat\Context\Step\Given as Given,
    Behat\Behat\Context\Step\When as When,
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
class behat_ucla extends behat_files {

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
     * Lock courses created to be in 14W, unless otherwise stated.
     * @var string
     */
    private $currentterm = '14W';

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
        // Restrict UCLA theme.
        set_config('themelist', 'uclashared,uclasharedcourse');
        // Set public/private.
        set_config('enablepublicprivate', 1);
        set_config('enablegroupmembersonly', 1);
        set_config('autologinguests', 1);
        // Set term.
        set_config('currentterm', $this->currentterm);
        set_config('forcedefaultmymoodle', 1);

        // Grading config variables.
        set_config('grader_report_grouping_filter', 1);
        set_config('collapsedefaultcolumns', 1, 'local_ucla');
        set_config('collapsesubmissionstatus', 1, 'local_ucla');
        set_config('defaultassignquickgrading', 1, 'local_ucla');
        set_config('defaultassignsettings', 1, 'local_ucla');
        set_config('showallgraderviewactions', 1, 'local_ucla');

        // Set other configs.
        set_config('showuseridentity', 'idnumber,email');

        // Enable course metalink plugin
        $enabled = array_keys(enrol_get_plugins(true));
        $enabled[] = 'meta';
        set_config('enrol_plugins_enabled', implode(',', $enabled));

        // Purge all caches to force new configs to take effect.
        purge_all_caches();
    }

    /**
     * Step to generate UCLA SRS + collab sites, and enrolments.
     *
     * NOTE: If you are creating activities, make sure to also set the idnumber
     * field.
     *
     * @Given /^the following ucla "([^"]*)" exist:$/
     *
     * @param string $elementname
     * @param TableNode $data
     */
    public function the_following_exist($elementname, TableNode $data) {
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
                        ->the_following_exist('course enrolments',
                                new TableNode($table));

                break;
            case 'activities':
                require_once(__DIR__ . '/../../../../local/publicprivate/lib/module.class.php');
                $this->getMainContext()->getSubcontext('behat_data_generators')
                        ->the_following_exist('activities', $data);
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
     * Since we can now specify fullname of a course in the UCLA data generator,
     * we can use the regular 'I follow "course fullname"' step for the most
     * part. But sometimes the only way to get to the course homepage easily is
     * to use this step.
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
                    // See if term was specified.
                    $term = $this->currentterm;
                    if (isset($elementdata['term'])) {
                        $term = $elementdata['term'];
                    }
                    $param = array('term' => $term);
                    if (isset($elementdata['fullname'])) {
                        $param['fullname'] = $elementdata['fullname'];
                    }
                    $class = $datagenerator->create_class($param);
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
     * @Given /^a ucla "([^"]*)" site exist$/
     * 
     * @param string $site type for a collab site, or 'class' for an SRS site
     */
    public function ucla_site_exist($site) {
        global $DB;

        $data = "| shortname | type |
                 | $site site | $site |";
        $this->generate_ucla_sites(new TableNode($data));

        // Call Moodle's own generator to create users and enrollments.
        // First create users.
        $data = '| username | firstname | lastname | email |
                | instructor | Editing | Instructor | instructor@asd.com |
                | student | Stu | Dent | student1@asd.com |';

        $this->getMainContext()->getSubcontext('behat_data_generators')
                ->the_following_exist('users', new TableNode($data));

        // Now create enrollments.
        $shortnames = array_keys($this->courses);    // Use newly created course above.
        $shortname = reset($shortnames);
        $data = "| user | course | role |
                | instructor | {$shortname} | editinginstructor |
                | student | {$shortname} | student |";

        $this->the_following_exist('course enrolments', new TableNode($data));
    }

    /**
     * Shortcut to navigate to the default UCLA site created by
     * "a ucla <site type> site exists".
     * 
     * @Given /^I go to the default ucla site$/
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
     * @deprecated since 2.7
     * @see behat_ucla::i_login_as_ucla_user()
     *
     * @Given /^I log in as ucla "([^"]*)"$/
     * @throws ElementNotFoundException
     * @param string $user
     */
    public function i_login_as_ucla_user($user) {
        $alternative = 'I log in as "' . $this->escape($user) . '"';
        $this->deprecated_message($alternative);
        return new Given($alternative);
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
     * Based off core step i_upload_file_to_filepicker, but it doesn't wait for
     * the file picker to return successfully.
     *
     * Future steps are expected to look for certain messages.
     *
     * @When /^I upload "(?P<filepath_string>(?:[^"]|\\")*)" file to "(?P<filepicker_field_string>(?:[^"]|\\")*)" filepicker and it fails$/
     * @throws ExpectationException Thrown by behat_base::find
     * @param string $filepath
     * @param string $filepickerelement
     */
    public function i_upload_file_to_filepicker_exception($filepath, $filepickerelement) {
        global $CFG;

        $filepickernode = $this->get_filepicker_node($filepickerelement);

        // Wait until file manager is completely loaded.
        $this->wait_until_contents_are_updated($filepickernode);

        // Opening the select repository window and selecting the upload repository.
        $this->open_add_file_window($filepickernode, get_string('pluginname', 'repository_upload'));

        // Ensure all the form is ready.
        $this->getSession()->wait(2 * 1000, false);
        $noformexception = new ExpectationException('The upload file form is not ready', $this->getSession());
        $this->find(
            'xpath',
            "//div[contains(concat(' ', normalize-space(@class), ' '), ' file-picker ')]" .
                "[contains(concat(' ', normalize-space(@class), ' '), ' repository_upload ')]" .
                "/descendant::div[@class='fp-content']" .
                "/descendant::div[contains(concat(' ', normalize-space(@class), ' '), ' fp-upload-form ')]" .
                "/descendant::form",
            $noformexception
        );
        // After this we have the elements we want to interact with.

        // Form elements to interact with.
        $file = $this->find_file('repo_upload_file');
        $submit = $this->find_button(get_string('upload', 'repository'));

        // Attaching specified file to the node.
        // Replace 'admin/' if it is in start of path with $CFG->admin .
        $pos = strpos($filepath, 'admin/');
        if ($pos === 0) {
            $filepath = $CFG->admin . DIRECTORY_SEPARATOR . substr($filepath, 6);
        }
        $filepath = str_replace('/', DIRECTORY_SEPARATOR, $filepath);
        $fileabsolutepath = $CFG->dirroot . DIRECTORY_SEPARATOR . $filepath;
        $file->attachFile($fileabsolutepath);

        // Submit the file.
        $submit->press();

        // Now look for exception message.
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
    
    /**
     * Adds an assignment filling the form data with the specified field/value pairs.
     * 
     * @Given /^I add an assignment and fill the form with:$/
     * @param TableNode $data The assignment field/value data
     */
    public function i_add_an_assignment_and_fill_the_form_with(TableNode $data) {
        return array(
            new Given('I follow "' . get_string('addresourceoractivity') . '"'),
            new Given ('I set the field "Assignment" to "1"'),
            new Given('I press "Add"'),
            new Given('I set the following fields to these values:', $data),
            new Given('I press "' . get_string('savechangesanddisplay') . '"')
        );
    }

    /**
     * Throws an exception if $CFG->behat_usedeprecated is not allowed.
     *
     * @throws Exception
     * @param string|array $alternatives Alternative/s to the requested step
     * @return void
     */
    protected function deprecated_message($alternatives) {
        global $CFG;

        // We do nothing if it is enabled.
        if (!empty($CFG->behat_usedeprecated)) {
            return;
        }

        if (is_scalar($alternatives)) {
            $alternatives = array($alternatives);
        }

        $message = 'Deprecated step, rather than using this step you can use:';
        foreach ($alternatives as $alternative) {
            $message .= PHP_EOL . '- ' . $alternative;
        }
        $message .= PHP_EOL . '- Set $CFG->behat_usedeprecated in config.php to allow the use of deprecated steps if you don\'t have any other option';
        throw new Exception($message);
    }

    /**
     * Step to generate UCLA SRS + collab sites, and enrolments.
     *
     * @deprecated since 2.7
     * @see behat_ucla::the_following_exist()
     *
     * @Given /^the following ucla "([^"]*)" exists:$/
     * @throws Exception
     * @throws PendingException
     * @param string $elementname
     * @param TableNode $data
     */
    public function the_following_ucla_exists($elementname, TableNode $data) {
        $alternative = 'the following ucla "' . $this->escape($elementname) . '" exist:';
        $this->deprecated_message($alternative);
        return new Given($alternative, $data);
    }

    /**
     * Generates a single UCLA site.  The site will have two enrolled
     * users, a student and an editing instructor.
     * 
     * @deprecated since 2.7
     * @see behat_ucla::ucla_site_exist()
     *
     * @Given /^a ucla "([^"]*)" site exists$/
     * @throws ElementNotFoundException
     * @param string $site type for a collab site, or 'class' for an SRS site
     */
    public function ucla_site_exists($site) {
        $alternative = 'a ucla "' . $this->escape($site) . '" site exist';
        $this->deprecated_message($alternative);
        return new Given($alternative);
    }

}
