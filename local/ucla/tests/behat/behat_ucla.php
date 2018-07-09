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

        // Set the name display.
        set_config('fullnamedisplay', 'lastname' . ", " . 'firstname');
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
        set_config('defaultassignquickgrading', 1, 'local_ucla');
        set_config('showallgraderviewactions', 1, 'local_ucla');

        // Set other configs.
        set_config('showuseridentity', 'idnumber,email');

        // Enable course metalink plugin.
        $enabled = array_keys(enrol_get_plugins(true));
        $enabled[] = 'meta';
        set_config('enrol_plugins_enabled', implode(',', $enabled));

        // Enable guest and site invitation plugin.
        set_config('status', ENROL_INSTANCE_ENABLED, 'enrol_guest');
        set_config('status', ENROL_INSTANCE_ENABLED, 'enrol_invitation');

        // Enable course requestor.
        set_config('enablecourserequests', 1);

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

                // Create table node to pass to Moodle's Behat step.
                $table = array();
                $table[] = array('user', 'course', 'role');

                $roles = array();
                foreach ($data->getHash() as $elementdata) {
                    $roles[] = $elementdata['role'];
                    $table[] = array($elementdata['user'],
                        $this->courses[$elementdata['course']]->shortname,
                        $elementdata['role']);
                }

                // Make sure that the proper UCLA roles exists.
                $this->get_data_generator()->create_ucla_roles($roles);

                // Forward the work to Moodle's Behat data generators.
                $this->execute('behat_data_generators::the_following_exist',
                        array('course enrolments', new TableNode($table)));

                break;

            case 'roles':
                // Import UCLA roles.
                $roles = array();
                foreach ($data->getHash() as $elementdata) {
                    $roles[] = $elementdata['role'];
                }
                $this->get_data_generator()->create_ucla_roles($roles);
                break;

            case 'role assigns':
                // Import UCLA roles.
                $roles = array();
                foreach ($data->getHash() as $elementdata) {
                    $roles[] = $elementdata['role'];
                }
                $this->get_data_generator()->create_ucla_roles($roles);

                // Forward the data to Moodle's data generators.
                $this->execute('behat_data_generators::the_following_exist',
                        array('role assigns', $data));
                break;

            case 'activities':
                require_once(__DIR__ . '/../../../../local/publicprivate/lib/module.class.php');
                $this->execute('behat_data_generators::the_following_exist',
                        array('activities', $data));
                // Make each activity either public or private (default).
                foreach ($data->getHash() as $elementdata) {
                    if (!empty($elementdata['private'])) {
                        // Find course module.
                        $cmid = $DB->get_field('course_modules', 'id',
                                array('idnumber' => $elementdata['idnumber']));
                        $ppmod = PublicPrivate_Module::build($cmid);
                        $ppmod->enable();
                    } else {
                        $cmid = $DB->get_field('course_modules', 'id',
                                array('idnumber' => $elementdata['idnumber']));
                        $ppmod = PublicPrivate_Module::build($cmid);
                        $ppmod->disable();
                    }
                }
        }
    }

    /**
     * Step to browse directly to any address in the Moodle root.
     *
     * @Given /^I am on "([^"]*)"$/
     *
     * @param string $url   Relative to Moodle root.
     */
    public function i_am_on($url) {
        $this->getSession()->visit($this->locate_path($url));
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
     * NOTE: For 'class' site types, this method will generate a separate shortname to be saved
     *       into the 'courses' db table.
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

                    // See if srs was specified.
                    if (isset($elementdata['srs'])) {
                        $param['srs'] = $elementdata['srs'];
                    }

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

        $data = array();
        $data[0] = array('fullname', 'shortname', 'type');
        $data[1] = array("$site site", "$site site", $site);
        $this->generate_ucla_sites(new TableNode($data));

        // Call Moodle's own generator to create users and enrollments.
        // First create users.
        $data = array();
        $data[0] = array('username', 'firstname', 'lastname', 'email');
        $data[1] = array('instructor', 'Editing', 'Instructor', 'instructor@asd.com');
        $data[2] = array('student', 'Stu', 'Dent', 'student1@asd.com');
        $this->execute('behat_data_generators::the_following_exist',
                array('users', new TableNode($data)));

        // Now create enrollments.
        $shortnames = array_keys($this->courses);    // Use newly created course above.
        $shortname = reset($shortnames);
        $data = array();
        $data[0] = array('user', 'course', 'role');
        $data[1] = array('instructor', $shortname, 'editinginstructor');
        $data[2] = array('student', $shortname, 'student');
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
     * Based off core step i_upload_file_to_filepicker, but it doesn't wait for
     * the file picker to return successfully.
     *
     * Future steps are expected to look for certain messages.
     *
     * Ignore coding standards error about long line. Necessary for Behat step.
     * @codingStandardsIgnoreLine
     * @When /^I upload "(?P<filepath_string>(?:[^"]|\\")*)" file to "(?P<filepicker_field_string>(?:[^"]|\\")*)" filemanager and it fails$/
     * @throws ExpectationException Thrown by behat_base::find
     * @param string $filepath
     * @param string $filepickerelement
     */
    public function i_upload_file_to_filemanager_exception($filepath, $filepickerelement) {
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
     * Set a private config setting with a value
     *
     * @Given /^I set the private config setting "([^"]*)" to "(\d+)";$/
     *
     * @param string $config
     * @param string $value
     */
    public function i_set_the_private_conig_setting_to($config, $value) {
        set_config($config, $value);
    }

    /**
     * Shortcut for clicking links in the left hand navigation.
     *
     * @When I follow the :section section in the ucla site menu
     *
     * @param string $section
     */
    public function i_follow_site_menu_section($section) {
        return array(
            new When('I click on "' . $section . '" "link" in the "#nav-drawer" "css_element"')
        );
    }

    /**
     * Checks that section is highlighted in the left hand navigation.
     *
     * @Then /^I should see "([^"]*)" highlighted in the ucla site menu$/
     *
     * @param string $section
     * @return array
     */
    public function i_should_see_higlighted($section) {
        // Note, for some reason this step is failing for "Site info".
        return array(
            new Then('I should see "' . $section . '" in the ".list-group-item[data-isactive=\'1\']" "css_element"')
        );
    }
    /**
     * Checks that section exists in the left hand navigation.
     *
     * @Then /^I should see "([^"]*)" in the ucla site menu$/
     *
     * @param string $section
     * @return array
     */
    public function i_should_see_in_site_menu($section) {
        return array(
            new Then('I should see "' . $section . '" in the "#nav-drawer" "css_element"')
        );
    }
    /**
     * Checks that section does not exist in the left hand navigation.
     *
     * @Then /^I should not see "([^"]*)" in the ucla site menu$/
     *
     * @param string $section
     * @return array
     */
    public function i_should_not_see_in_site_menu($section) {
        return array(
            new Then('I should not see "' . $section . '" in the "#nav-drawer" "css_element"')
        );
    }
    /**
     * Checks if a site menu section contains the 'hidden' label.
     *
     * @Given /^the "([^"]*)" section in the ucla site menu is hidden$/
     * @param string $section
     */
    public function the_site_menu_section_hidden($section) {
        // Find the hidden section containing the section name text.
        $xpath = "//*[contains(@class, 'block_ucla_course_menu_hidden')]/*[contains(.,'$section')]";
        $hiddensections = $this->find('xpath', $xpath);
        if (empty($hiddensections)) {
            throw new ExpectationException('The section "' . $section . '" does not have the "hidden" label.', $this->getSession());
        }
    }
    /**
     * Checks that a site menu section does NOT have a 'hidden' label.
     *
     * @Given /^the "([^"]*)" section in the ucla site menu is visible$/
     * @param string $section
     */
    public function the_site_menu_section_visible($section) {
        try {
            $this->the_site_menu_section_hidden($section);
        } catch (Exception $e) {
            // This is good.
            return;
        }
        throw new ExpectationException('The section "' . $section . '" has a "hidden" label.', $this->getSession());
    }

}
