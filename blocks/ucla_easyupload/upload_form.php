<?php
// This file is part of UCLA Easy Upload plugin for Moodle - http://moodle.org/
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
 * Contains the abstract class easy_upload_form.
 *
 * @package    block_ucla_easyupload
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/local/ucla_syllabus/locallib.php');

/**
 * Abstract class extended by easyupload_{type}_form classes in ./upload_types/.
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class easy_upload_form extends moodleform {
    /**
     * The course stdClass, located in the moodleform's _customdata.
     * @var stdClass
     */
    protected $course;
    /**
     * The moodleform's activities, located in its _customdata.
     * @var array
     */
    protected $activities;
    /**
     * The moodleform's resources, located in its _customdata.
     * @var array
     */
    protected $resources;
    /**
     * The course's context instance.
     * @var context_course context instance
     */
    protected $context;

    /**
     * The block associated with each form.
     * @var string 'block_ucla_easyupload'
     */
    const ASSOCIATED_BLOCK = 'block_ucla_easyupload';

    // Setting these parameters to private seems to break stuff.
    /**
     * This will enable the section switcher.
     * @var boolean
     */
    public $allowjsselect = false;

    /**
     * This will enable the naming field.
     * @var boolean
     */
    public $allowrenaming = false;

    /**
     * This will enable the public private stuff.
     * @var boolean
     */
    public $allowpublicprivate = true;

    /**
     * This will enable the availability sliders.
     * @var boolean
     */
    public $enableavailability = true;

    /**
     * This is the default publicprivate, if relevant,
     * @var string 'public' or 'private'
     */
    public $defaultpublicprivate = 'private';

    /**
     * This is a hack for labels, but just in general
     * it should refer to the field that is displayed that will
     * help associate the javascript updating the rearrange.
     * @var string 'name'
     */
    public $defaultdisplaynamefield = 'name';

    /**
     * Called by moodleforms when we are rendering the form.
     */
    public function definition() {
        global $CFG, $PAGE;

        $mform = $this->_form;

        $course = $this->_customdata['course'];

        $this->course = $course;

        $acts = $this->_customdata['activities'];
        $this->activities = $acts;

        $reso = $this->_customdata['resources'];
        $this->resources = $reso;

        $this->context = context_course::instance($course->id);

        $type = $this->_customdata['type'];
        $sections = $this->_customdata['sectionnames'];
        $rearrangeavail = $this->_customdata['rearrange'];

        $defaultsection = $this->_customdata['defaultsection'];

        $addtitle = 'dialog_add_' . $type;
        $mform->addElement('header', 'general', get_string($addtitle,
            self::ASSOCIATED_BLOCK));

        // Adding needed parameters if being redirected or adding amodule.
        $mform->addElement('hidden', 'course_id', $course->id);
        $mform->setType('course_id', PARAM_INT);

        $mform->addElement('hidden', 'course', $course->id);
        $mform->setType('course', PARAM_INT);

        $mform->addElement('hidden', 'type', $type, array('id' => 'id_type'));
        $mform->setType('type', PARAM_ALPHANUM);

        $mform->addElement('hidden', 'modulename', $this->get_coursemodule());
        $mform->setType('modulename', PARAM_ALPHANUM);

        $mform->addElement('hidden', 'defaultdisplaynamefield',
            $this->defaultdisplaynamefield,
            array('id' => 'id_defaultdisplaynamefield'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('defaultdisplaynamefield', PARAM_TEXT);
        } else {
            $mform->setType('defaultdisplaynamefield', PARAM_CLEANHTML);
        }

        // Use whatever the default display type is for the site. Can be either
        // automatic, embed, force download, etc. Look in lib/resourcelib.php
        // for other types.
        $mform->addElement('hidden', 'display', get_config('resource', 'display'));
        $mform->setType('display', PARAM_INT);

        // Configure what it is you exactly are adding.
        $this->specification();

        if ($this->allowrenaming) {
            $renametitle = 'dialog_rename_' . $type;
            $mform->addElement('header', '', get_string($renametitle,
                self::ASSOCIATED_BLOCK));
            $mform->addElement('static', 'syllabus', '', '<span id="id_syllabus_prompt"></span>');
            $mform->addElement('text', 'name', get_string('name'),
                array('size' => 40));
            $mform->addRule('name', null, 'required');
            $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
            if (!empty($CFG->formatstringstriptags)) {
                $mform->setType('name', PARAM_TEXT);
            } else {
                $mform->setType('name', PARAM_CLEANHTML);
            }

            $mform->addElement('editor', 'introeditor', get_string('description'), array('rows' => 3),
                array('maxfiles' => EDITOR_UNLIMITED_FILES,
                'noclean' => true, 'context' => $this->context, 'collapsed' => true));
        }

        if (class_exists('PublicPrivate_Site') && $this->allowpublicprivate) {
            if (PublicPrivate_Site::is_enabled()) {
                // Make sure public/private is enabled for course.
                $ppcourse = PublicPrivate_Course::build($course);
                if ($ppcourse->is_activated()) {
                    // SSC-1928 - Make Upload default to Public if Name contains Syllabus
                    //
                    // Generate the public private elements.
                    //
                    // The public_warning span is the anchor point for some JavaScript
                    // that displays text when someone uploads a syllabus with
                    // public/private radios set to private. The syllabus_prompt
                    // span is the anchor point for the same JavaScript
                    // suggests instructors to use the Syllabus Tool instead.
                    $mform->addElement('header', '', get_string(
                        'publicprivateenable', 'local_publicprivate'));

                    $pubpriels[] = $mform->createElement('radio', 'publicprivate', 'publicbutton',
                        'Public'. get_string('upload_public_file', self::ASSOCIATED_BLOCK)
                        . '<span style="color: red" id="id_public_warning"></span>', 'public');
                    $pubpriels[] = $mform->createElement('radio', 'publicprivate', 'privatebutton',
                        'Private' . get_string('upload_private_file',  self::ASSOCIATED_BLOCK), 'private');
                    $mform->addGroup($pubpriels, 'publicprivateradios', '', '<br>', true);
                    $mform->setDefaults(
                        array(
                            'publicprivateradios' => array(
                                'publicprivate' => $this->defaultpublicprivate
                            )
                        )
                    );

                    $mform->addHelpButton('publicprivateradios', 'syllabus_help_button', 'block_ucla_easyupload');
                }
            }
        }

        // Section selection.
        $mform->addElement('header', '', get_string('select_section',
            self::ASSOCIATED_BLOCK));

        // Show the section selector.
        $mform->addElement('select', 'section',
            get_string('select_section',
            self::ASSOCIATED_BLOCK), $sections);
        $mform->setDefault('section', $defaultsection);

        // If needed, add the section rearranges.
        // This part appears to be a part of 'add to section'.
        if ($rearrangeavail && $this->allowjsselect) {
            global $PAGE;

            $mform->addElement('hidden', 'serialized', null,
                    array('id' => 'serialized'));
            $mform->setType('serialized', PARAM_RAW);

            $mform->addElement('html', html_writer::tag('div',
                    html_writer::tag('ul', get_string('rearrangejsrequired',
                    self::ASSOCIATED_BLOCK), array('id' => 'thelist')),
                array('id' => 'reorder-container'))
            );

            $PAGE->requires->js_init_code(
                'M.block_ucla_easyupload.initiate_sortable_content()'
            );
        }

        $syllabusmanager = new ucla_syllabus_manager($course);
        if ($syllabusmanager->can_host_syllabi()) {
            // SSC-1928 - Make Upload default to Public if Name contains Syllabus.
            // Create a JavaScript function call that gets (continually?) called
            // when on the form.
            $PAGE->requires->js_init_code('M.block_ucla_easyupload.syllabus_default_public()');

            // Place code to grab language strings for the syllabus function!
            // Get course ID for url.
            $cpurl = new moodle_url('/local/ucla_syllabus/index.php?id=' . $course->id);
            // Generate url string to pass to js function.
            $jsparam = '<a href="' . $cpurl . '">' . get_string('syllabus_tool_name', 'block_ucla_easyupload') . '</a>';

            // Pass in strings for the JavaScript function to use.
            $PAGE->requires->string_for_js('default_change', 'block_ucla_easyupload');
            $PAGE->requires->string_for_js('syllabus_box_body', 'block_ucla_easyupload', $jsparam);
        }

        // From /course/moodleform_mod.php:448 (Moodle 2.7) with modifications.
        if (!empty($CFG->enableavailability)) {
            // Availability field. This is just a textarea; the user interface
            // interaction is all implemented in JavaScript.
            $mform->addElement('header', 'availabilityconditionsheader',
                    get_string('restrictaccess', 'availability'));
            // Note: This field cannot be named 'availability' because that
            // conflicts with fields in existing modules (such as assign).
            // So it uses a long name that will not conflict.
            $mform->addElement('textarea', 'availabilityconditionsjson',
                    get_string('accessrestrictions', 'availability'));

            \core_availability\frontend::include_all_javascript($course);
        }
        // END code from /course/moodleform_mod.php to display availability restrictions.

        $this->add_action_buttons();
    }

    /**
     * Called within the form, to specify what it is the form is specifying
     * from the user.
     */
    abstract public function specification();

    /**
     * Called when attempting to figure out what module to add.
     * This is simply an enforcement protocol, this function is
     * actually called within definition() and added as a value
     * to a hidden field within the form.
     *
     * @return string
     */
    abstract public function get_coursemodule();

    /**
     *  Validation for availability.
     * @param stdClass $data
     * @param array $files
     */
    public function validation($data, $files) {
        // Conditions: Don't let them set dates which make no sense.
        if (array_key_exists('availablefrom', $data) &&
            $data['availablefrom'] && $data['availableuntil'] &&
            $data['availablefrom'] >= $data['availableuntil']) {
            $errors['availablefrom'] = get_string('badavailabledates', 'condition');
        }
    }
}