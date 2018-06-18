<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir. '/coursecatlib.php');
// START UCLA MOD
require_once($CFG->dirroot.'/local/publicprivate/lib/course.class.php');
require_once($CFG->dirroot.'/local/publicprivate/lib/site.class.php');
require_once($CFG->dirroot . '/admin/tool/uclasiteindicator/lib.php');
// END UCLA MOD

/**
 * The form for handling editing a course.
 */
class course_edit_form extends moodleform {
    protected $course;
    protected $context;

    /**
     * Form definition.
     */
    function definition() {
        global $CFG, $PAGE;

        $mform    = $this->_form;
        $PAGE->requires->yui_module('moodle-course-formatchooser', 'M.course.init_formatchooser',
                array(array('formid' => $mform->getAttribute('id'))));

        $course        = $this->_customdata['course']; // this contains the data of this form
        $category      = $this->_customdata['category'];
        $editoroptions = $this->_customdata['editoroptions'];
        $returnto = $this->_customdata['returnto'];
        $returnurl = $this->_customdata['returnurl'];

        $systemcontext   = context_system::instance();
        $categorycontext = context_coursecat::instance($category->id);

        if (!empty($course->id)) {
            $coursecontext = context_course::instance($course->id);
            $context = $coursecontext;
        } else {
            $coursecontext = null;
            $context = $categorycontext;
        }

        $courseconfig = get_config('moodlecourse');

        $this->course  = $course;
        $this->context = $context;
        
        // START UCLAMOD CCLE-2389 - site indicator info display
        
        if(!empty($course->id) && ucla_map_courseid_to_termsrses($this->course->id)) {
            // is a registrar site
//            $mform->addElement('static', 'indicator', 
//                    get_string('type', 'tool_uclasiteindicator'), 
//                    get_string('site_registrar', 'tool_uclasiteindicator'));
        } else if (course_get_format($course)->get_format() == 'ucla') {
            // user can assign site type if they have the capability at site, 
            // category, or course level
            $can_edit_sitetype = false;
            if (has_capability('tool/uclasiteindicator:edit', $systemcontext) || 
                    has_capability('tool/uclasiteindicator:edit', $categorycontext) ||
                    (!empty($coursecontext) && has_capability('tool/uclasiteindicator:edit', $coursecontext))) {
                $can_edit_sitetype = true;
            }

            $indicator = null;
            if (!empty($course->id)) {
                $indicator = siteindicator_site::load($course->id);
            }            

            // do not allow TA site type to be changed via GUI
            if (!empty($indicator) &&
                    $indicator->property->type == siteindicator_manager::SITE_TYPE_TASITE) {
                $can_edit_sitetype = false;
            }


            // only display site type info if there is a type and user can edit
            if ($can_edit_sitetype || !empty($indicator)) {
                $mform->addElement('header','uclasiteindicator', get_string('pluginname', 'tool_uclasiteindicator'));
            }
            
            // If user can edit site type, then they can also edit category, so 
            // remind user that if course is in default category that they 
            // should move it.
            if ($can_edit_sitetype && $category->id == $CFG->defaultrequestcategory) {
                global $OUTPUT;
                $mform->addElement('html', $OUTPUT->notification(
                                get_string('defaultcategorywarning', 'tool_uclasiteindicator'), 
                                'notifywarning'));                
            }            
            
            if(!empty($indicator)) {                
                $indicator_type = html_writer::tag('strong',
                        siteindicator_manager::get_types_list($indicator->property->type));
                $mform->addElement('static', 'indicator', get_string('type', 'tool_uclasiteindicator'), 
                        $indicator_type);
                
                $roles = $indicator->get_assignable_roles();
                $rolenames = array();
                foreach ($roles as $role) {
                    $rolenames[] = $role->name;
                }
                $mform->addElement('static', 'indicator_roles', get_string('roles', 'tool_uclasiteindicator'), 
                        '<strong>' . implode('</strong>, <strong>', $rolenames) . '</strong>');
            }
                                
            // Change the site type
            if($can_edit_sitetype) {
                if (empty($indicator)) {
                    // no indicator found, display ability for user to choose type
                    // if they have the capability to edit
                    $indicator_type = get_string('no_indicator_type', 'tool_uclasiteindicator');
                    $mform->addElement('static', 'indicator', get_string('type', 'tool_uclasiteindicator'), 
                            $indicator_type);                    
                }

                $types = siteindicator_manager::get_types_list();
                $radioarray = array();
                foreach($types as $type) {
                    // don't allow tasite type to be selected
                    if (siteindicator_manager::SITE_TYPE_TASITE == $type['shortname']) {
                        continue;
                    }
                    $descstring = '<strong>' . $type['fullname'] . '</strong> - ' . $type['description'];
                    $attributes = array(
                        'class' => 'indicator-form',
                        'value' => $type['shortname']
                    );
                    $radioarray[] = $mform->createElement('radio', 'indicator_change', '', $descstring, $type['shortname'], $attributes);
                }
                $mform->addGroup($radioarray, 'indicator_type_radios', get_string('change', 'tool_uclasiteindicator'), array('<br/>'), false);
                $mform->addGroupRule('indicator_type_radios', get_string('required'), 'required');
                
                if (!empty($indicator)) {
                    $mform->setDefault('indicator_change', $indicator->property->type);
                }
            }            
        }
        // END UCLA MOD CCLE-2389

        // Form definition with new course defaults.
        $mform->addElement('header','general', get_string('general', 'form'));

        $mform->addElement('hidden', 'returnto', null);
        $mform->setType('returnto', PARAM_ALPHANUM);
        $mform->setConstant('returnto', $returnto);

        $mform->addElement('hidden', 'returnurl', null);
        $mform->setType('returnurl', PARAM_LOCALURL);
        $mform->setConstant('returnurl', $returnurl);

        $mform->addElement('text','fullname', get_string('fullnamecourse'),'maxlength="254" size="50"');
        $mform->addHelpButton('fullname', 'fullnamecourse');
        $mform->addRule('fullname', get_string('missingfullname'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_TEXT);
        if (!empty($course->id) and !has_capability('moodle/course:changefullname', $coursecontext)) {
            $mform->hardFreeze('fullname');
            $mform->setConstant('fullname', $course->fullname);
        }

        $mform->addElement('text', 'shortname', get_string('shortnamecourse'), 'maxlength="100" size="20"');
        $mform->addHelpButton('shortname', 'shortnamecourse');
        $mform->addRule('shortname', get_string('missingshortname'), 'required', null, 'client');
        $mform->setType('shortname', PARAM_TEXT);
        if (!empty($course->id) and !has_capability('moodle/course:changeshortname', $coursecontext)) {
            $mform->hardFreeze('shortname');
            $mform->setConstant('shortname', $course->shortname);
        }

        // Verify permissions to change course category or keep current.
        if (empty($course->id)) {
            if (has_capability('moodle/course:create', $categorycontext)) {
                $displaylist = coursecat::make_categories_list('moodle/course:create');
                $mform->addElement('select', 'category', get_string('coursecategory'), $displaylist);
                $mform->addHelpButton('category', 'coursecategory');
                $mform->setDefault('category', $category->id);
            } else {
                $mform->addElement('hidden', 'category', null);
                $mform->setType('category', PARAM_INT);
                $mform->setConstant('category', $category->id);
            }
        } else {
            if (has_capability('moodle/course:changecategory', $coursecontext)) {
                $displaylist = coursecat::make_categories_list('moodle/course:changecategory');
                if (!isset($displaylist[$course->category])) {
                    //always keep current
                    $displaylist[$course->category] = coursecat::get($course->category, MUST_EXIST, true)->get_formatted_name();
                }
                $mform->addElement('select', 'category', get_string('coursecategory'), $displaylist);
                $mform->addHelpButton('category', 'coursecategory');
            } else {
                //keep current
                $mform->addElement('hidden', 'category', null);
                $mform->setType('category', PARAM_INT);
                $mform->setConstant('category', $course->category);
            }
        }

        // START UCLA MOD: CCLE-6987 - Course visibility, Duplicate locations.
        /*
        $choices = array();
        $choices['0'] = get_string('hide');
        $choices['1'] = get_string('show');
        $mform->addElement('select', 'visible', get_string('coursevisibility'), $choices);
        $mform->addHelpButton('visible', 'coursevisibility');
        $mform->setDefault('visible', $courseconfig->visible);
        if (!empty($course->id)) {
            if (!has_capability('moodle/course:visibility', $coursecontext)) {
                $mform->hardFreeze('visible');
                $mform->setConstant('visible', $course->visible);
            }
        } else {
            if (!guess_if_creator_will_have_course_capability('moodle/course:visibility', $categorycontext)) {
                $mform->hardFreeze('visible');
                $mform->setConstant('visible', $courseconfig->visible);
            }
        }
        */
        // END UCLA MOD: CCLE-6987.

        $mform->addElement('date_selector', 'startdate', get_string('startdate'));
        $mform->addHelpButton('startdate', 'startdate');
        $mform->setDefault('startdate', time() + 3600 * 24);

        $mform->addElement('date_selector', 'enddate', get_string('enddate'), array('optional' => true));
        $mform->addHelpButton('enddate', 'enddate');

        // START UCLA MOD: CCLE-2940 - TERM-SRS Numbers needed in Course ID Number field
        // We aren't using idnumber to put in term-srs anymore, so just query
        // for term-srs using the cross-listing api and put in the results as
        // a constant.
//        $mform->addElement('text','idnumber', get_string('idnumbercourse'),'maxlength="100"  size="10"');
//        $mform->addHelpButton('idnumber', 'idnumbercourse');
//        $mform->setType('idnumber', PARAM_RAW);
//        if (!empty($course->id) and !has_capability('moodle/course:changeidnumber', $coursecontext)) {
//            $mform->hardFreeze('idnumber');
//            $mform->setConstants('idnumber', $course->idnumber);
//        }
        $mform->addElement('static','idnumber', get_string('idnumbercourse'));
        $mform->addHelpButton('idnumber', 'idnumbercourse');
        if (!empty($course->id)) {
            // Only query for term-srs if course exists.
            require_once($CFG->dirroot . '/local/ucla/lib.php');
            $courseinfo = ucla_get_course_info($course->id);
            $idnumber = '';
            if (!empty($courseinfo)) {
                // Create string.
                $firstentry = true;
                foreach ($courseinfo as $courserecord) {
                    $firstentry ? $firstentry = false : $idnumber .= ', ';
                    $idnumber .= sprintf('%s (%s)', 
                            ucla_make_course_title($courserecord),
                            make_idnumber($courserecord));
                }
            }
            $course->idnumber = $idnumber;
        }
        // END UCLA MOD CCLE-2940

        // Description.
        $mform->addElement('header', 'descriptionhdr', get_string('description'));
        $mform->setExpanded('descriptionhdr');

        $mform->addElement('editor','summary_editor', get_string('coursesummary'), null, $editoroptions);
        $mform->addHelpButton('summary_editor', 'coursesummary');
        $mform->setType('summary_editor', PARAM_RAW);
        $summaryfields = 'summary_editor';

        // START UCLA MOD: CCLE-4869 - Unable to display images Course Summary text editor
        // if ($overviewfilesoptions = course_overviewfiles_options($course)) {
        //    $mform->addElement('filemanager', 'overviewfiles_filemanager', get_string('courseoverviewfiles'), null, $overviewfilesoptions);
        //    $mform->addHelpButton('overviewfiles_filemanager', 'courseoverviewfiles');
        //    $summaryfields .= ',overviewfiles_filemanager';
        // }
        // END UCLA MOD: CCLE-4869

        if (!empty($course->id) and !has_capability('moodle/course:changesummary', $coursecontext)) {
            // Remove the description header it does not contain anything any more.
            $mform->removeElement('descriptionhdr');
            $mform->hardFreeze($summaryfields);
        }

        // Course format.
        $mform->addElement('header', 'courseformathdr', get_string('type_format', 'plugin'));

        $courseformats = get_sorted_course_formats(true);
        $formcourseformats = array();
        foreach ($courseformats as $courseformat) {
            $formcourseformats[$courseformat] = get_string('pluginname', "format_$courseformat");
        }
        if (isset($course->format)) {
            $course->format = course_get_format($course)->get_format(); // replace with default if not found
            if (!in_array($course->format, $courseformats)) {
                // this format is disabled. Still display it in the dropdown
                $formcourseformats[$course->format] = get_string('withdisablednote', 'moodle',
                        get_string('pluginname', 'format_'.$course->format));
            }
        }

        $mform->addElement('select', 'format', get_string('format'), $formcourseformats);
        $mform->addHelpButton('format', 'format');
        $mform->setDefault('format', $courseconfig->format);

        // Button to update format-specific options on format change (will be hidden by JavaScript).
        $mform->registerNoSubmitButton('updatecourseformat');
        $mform->addElement('submit', 'updatecourseformat', get_string('courseformatudpate'));

        // Just a placeholder for the course format options.
        $mform->addElement('hidden', 'addcourseformatoptionshere');
        $mform->setType('addcourseformatoptionshere', PARAM_BOOL);

        // Appearance.
        $mform->addElement('header', 'appearancehdr', get_string('appearance'));
        if (!empty($CFG->allowcoursethemes)) {
            $themeobjects = get_list_of_themes();
            $themes=array();
            $themes[''] = get_string('forceno');
            foreach ($themeobjects as $key=>$theme) {
                if (empty($theme->hidefromselector)) {
                    $themes[$key] = get_string('pluginname', 'theme_'.$theme->name);
                }
            }
            $mform->addElement('select', 'theme', get_string('forcetheme'), $themes);
            // START UCLA MOD: CCLE-2315 - CUSTOM DEPARTMENT THEMES
            // If we're using the uclasharedcourse theme, we want to allow a course
            // to upload extra logos.
            global $OUTPUT;
            if(!empty($OUTPUT->coursetheme)) {
                $data = $OUTPUT->edit_form_filepicker($mform, $course->id, $coursecontext->id);
                $this->set_data($data);
            }
            // END UCLA MOD CCLE-2315
        }

        $languages=array();
        $languages[''] = get_string('forceno');
        $languages += get_string_manager()->get_list_of_translations();
        $mform->addElement('select', 'lang', get_string('forcelanguage'), $languages);
        $mform->setDefault('lang', $courseconfig->lang);

        // Multi-Calendar Support - see MDL-18375.
        $calendartypes = \core_calendar\type_factory::get_list_of_calendar_types();
        // We do not want to show this option unless there is more than one calendar type to display.
        if (count($calendartypes) > 1) {
            $calendars = array();
            $calendars[''] = get_string('forceno');
            $calendars += $calendartypes;
            $mform->addElement('select', 'calendartype', get_string('forcecalendartype', 'calendar'), $calendars);
        }

        $options = range(0, 10);
        $mform->addElement('select', 'newsitems', get_string('newsitemsnumber'), $options);
        $courseconfig = get_config('moodlecourse');
        $mform->setDefault('newsitems', $courseconfig->newsitems);
        $mform->addHelpButton('newsitems', 'newsitemsnumber');

        $mform->addElement('selectyesno', 'showgrades', get_string('showgrades'));
        $mform->addHelpButton('showgrades', 'showgrades');
        $mform->setDefault('showgrades', $courseconfig->showgrades);

        $mform->addElement('selectyesno', 'showreports', get_string('showreports'));
        $mform->addHelpButton('showreports', 'showreports');
        $mform->setDefault('showreports', $courseconfig->showreports);

        // Files and uploads.
        $mform->addElement('header', 'filehdr', get_string('filesanduploads'));

        if (!empty($course->legacyfiles) or !empty($CFG->legacyfilesinnewcourses)) {
            if (empty($course->legacyfiles)) {
                //0 or missing means no legacy files ever used in this course - new course or nobody turned on legacy files yet
                $choices = array('0'=>get_string('no'), '2'=>get_string('yes'));
            } else {
                $choices = array('1'=>get_string('no'), '2'=>get_string('yes'));
            }
            $mform->addElement('select', 'legacyfiles', get_string('courselegacyfiles'), $choices);
            $mform->addHelpButton('legacyfiles', 'courselegacyfiles');
            if (!isset($courseconfig->legacyfiles)) {
                // in case this was not initialised properly due to switching of $CFG->legacyfilesinnewcourses
                $courseconfig->legacyfiles = 0;
            }
            $mform->setDefault('legacyfiles', $courseconfig->legacyfiles);
        }

        // Handle non-existing $course->maxbytes on course creation.
        $coursemaxbytes = !isset($course->maxbytes) ? null : $course->maxbytes;

        // Let's prepare the maxbytes popup.
        $choices = get_max_upload_sizes($CFG->maxbytes, 0, 0, $coursemaxbytes);
        $mform->addElement('select', 'maxbytes', get_string('maximumupload'), $choices);
        $mform->addHelpButton('maxbytes', 'maximumupload');
        $mform->setDefault('maxbytes', $courseconfig->maxbytes);

        // Completion tracking.
        if (completion_info::is_enabled_for_site()) {
            $mform->addElement('header', 'completionhdr', get_string('completion', 'completion'));
            $mform->addElement('selectyesno', 'enablecompletion', get_string('enablecompletion', 'completion'));
            $mform->setDefault('enablecompletion', $courseconfig->enablecompletion);
            $mform->addHelpButton('enablecompletion', 'enablecompletion', 'completion');
        } else {
            $mform->addElement('hidden', 'enablecompletion');
            $mform->setType('enablecompletion', PARAM_INT);
            $mform->setDefault('enablecompletion', 0);
        }

        enrol_course_edit_form($mform, $course, $context);

        $mform->addElement('header','groups', get_string('groupsettingsheader', 'group'));

        // START UCLA MOD
        /**
         * Flag to enable or disable public/private if it is enabled for the
         * site or if it is activated for the course.
         *
         * @author ebollens
         * @version 20110719
         */
        if(PublicPrivate_Site::is_enabled() || (PublicPrivate_Course::is_publicprivate_capable($course) 
                && PublicPrivate_Course::build($course)->is_activated())) {
            $choices = array();
            $choices[0] = get_string('disable');
            $choices[1] = get_string('enable');
            $mform->addElement('select', 'enablepublicprivate', get_string('publicprivate','local_publicprivate'), $choices);
            $mform->addHelpButton('enablepublicprivate', 'publicprivateenable', 'local_publicprivate');
            $mform->setDefault('enablepublicprivate', empty($course->enablepublicprivate) ? 1 : $course->enablepublicprivate);
        }
        // END UCLA MOD

        $choices = array();
        $choices[NOGROUPS] = get_string('groupsnone', 'group');
        $choices[SEPARATEGROUPS] = get_string('groupsseparate', 'group');
        $choices[VISIBLEGROUPS] = get_string('groupsvisible', 'group');
        $mform->addElement('select', 'groupmode', get_string('groupmode', 'group'), $choices);
        $mform->addHelpButton('groupmode', 'groupmode', 'group');
        $mform->setDefault('groupmode', $courseconfig->groupmode);

        $mform->addElement('selectyesno', 'groupmodeforce', get_string('groupmodeforce', 'group'));
        $mform->addHelpButton('groupmodeforce', 'groupmodeforce', 'group');
        $mform->setDefault('groupmodeforce', $courseconfig->groupmodeforce);

        //default groupings selector
        $options = array();
        $options[0] = get_string('none');
        $mform->addElement('select', 'defaultgroupingid', get_string('defaultgrouping', 'group'), $options);

        if ((empty($course->id) && guess_if_creator_will_have_course_capability('moodle/course:renameroles', $categorycontext))
                || (!empty($course->id) && has_capability('moodle/course:renameroles', $coursecontext))) {
            // Customizable role names in this course.
            $mform->addElement('header', 'rolerenaming', get_string('rolerenaming'));
            $mform->addHelpButton('rolerenaming', 'rolerenaming');

            if ($roles = get_all_roles()) {
                $roles = role_fix_names($roles, null, ROLENAME_ORIGINAL);
                $assignableroles = get_roles_for_contextlevels(CONTEXT_COURSE);
                foreach ($roles as $role) {
                    $mform->addElement('text', 'role_' . $role->id, get_string('yourwordforx', '', $role->localname));
                    $mform->setType('role_' . $role->id, PARAM_TEXT);
                }
            }
        }

        if (core_tag_tag::is_enabled('core', 'course') &&
                ((empty($course->id) && guess_if_creator_will_have_course_capability('moodle/course:tag', $categorycontext))
                || (!empty($course->id) && has_capability('moodle/course:tag', $coursecontext)))) {
            $mform->addElement('header', 'tagshdr', get_string('tags', 'tag'));
            $mform->addElement('tags', 'tags', get_string('tags'),
                    array('itemtype' => 'course', 'component' => 'core'));
        }

        // When two elements we need a group.
        $buttonarray = array();
        $classarray = array('class' => 'form-submit');
        if ($returnto !== 0) {
            $buttonarray[] = &$mform->createElement('submit', 'saveandreturn', get_string('savechangesandreturn'), $classarray);
        }
        $buttonarray[] = &$mform->createElement('submit', 'saveanddisplay', get_string('savechangesanddisplay'), $classarray);
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

        $mform->addElement('hidden', 'id', null);
        $mform->setType('id', PARAM_INT);

        // Finally set the current form data
        $this->set_data($course);
    }

    /**
     * Fill in the current page data for this course.
     */
    function definition_after_data() {
        global $DB;

        $mform = $this->_form;

        // add available groupings
        $courseid = $mform->getElementValue('id');
        if ($courseid and $mform->elementExists('defaultgroupingid')) {
            $options = array();
            if ($groupings = $DB->get_records('groupings', array('courseid'=>$courseid))) {
                foreach ($groupings as $grouping) {
                    $options[$grouping->id] = format_string($grouping->name);
                }
            }
            core_collator::asort($options);
            $gr_el =& $mform->getElement('defaultgroupingid');
            $gr_el->load($options);
        }

        // add course format options
        $formatvalue = $mform->getElementValue('format');
        if (is_array($formatvalue) && !empty($formatvalue)) {

            $params = array('format' => $formatvalue[0]);
            // Load the course as well if it is available, course formats may need it to work out
            // they preferred course end date.
            if ($courseid) {
                $params['id'] = $courseid;
            }
            $courseformat = course_get_format((object)$params);

            $elements = $courseformat->create_edit_form_elements($mform);
            for ($i = 0; $i < count($elements); $i++) {
                $mform->insertElementBefore($mform->removeElement($elements[$i]->getName(), false),
                        'addcourseformatoptionshere');
            }

            // Remove newsitems element if format does not support news.
            if (!$courseformat->supports_news()) {
                $mform->removeElement('newsitems');
            }
        }

        // START UCLA MOD: CCLE-4230 - Instructors can change format from UCLA
        if ($courseid = $mform->getElementValue('id')) {
            $context = context_course::instance($courseid);

            // Lock down ability for non-admins to change course format,
            // language, and file upload size.
            if (!empty($context) &&
                !has_capability('local/ucla:editadvancedcoursesettings', $context)) {
                $lockdown = array('format', 'lang', 'maxbytes');
                foreach ($lockdown as $elementname) {
                    $element = $mform->getElement($elementname);
                    // Unfortunately, if an element cannot be found, it doesn't
                    // throw an exception. Instead, QuickForm will print
                    // debugging messages and return an HTML_QuickForm_Error
                    // object.
                    if (get_class($element) == 'HTML_QuickForm_Error') {
                        continue;
                    }
                    $element->freeze();
                }
            }

            // Lock down abilty to change themes. Should allow themes to be
            // changed to uclasharedcourse theme, if user has capability.
            $editcoursetheme = false;
            if (!empty($context) && has_capability('local/ucla:editcoursetheme', $context)) {
                $editcoursetheme = true;
            }
            if (!$editcoursetheme) {
                $element = $mform->getElement('theme');
                $element->freeze();
            } else {
                $element = $mform->getElement('theme');
                
            }
        }
        // END UCLA MOD: CCLE-4230
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array the errors that were found
     */
    function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        // Add field validation check for duplicate shortname.
        if ($course = $DB->get_record('course', array('shortname' => $data['shortname']), '*', IGNORE_MULTIPLE)) {
            if (empty($data['id']) || $course->id != $data['id']) {
                $errors['shortname'] = get_string('shortnametaken', '', $course->fullname);
            }
        }

        // Add field validation check for duplicate idnumber.
        if (!empty($data['idnumber']) && (empty($data['id']) || $this->course->idnumber != $data['idnumber'])) {
            if ($course = $DB->get_record('course', array('idnumber' => $data['idnumber']), '*', IGNORE_MULTIPLE)) {
                if (empty($data['id']) || $course->id != $data['id']) {
                    $errors['idnumber'] = get_string('courseidnumbertaken', 'error', $course->fullname);
                }
            }
        }

        if ($errorcode = course_validate_dates($data)) {
            $errors['enddate'] = get_string($errorcode, 'error');
        }

        $errors = array_merge($errors, enrol_course_edit_validation($data, $this->context));

        $courseformat = course_get_format((object)array('format' => $data['format']));
        $formaterrors = $courseformat->edit_form_validation($data, $files, $errors);
        if (!empty($formaterrors) && is_array($formaterrors)) {
            $errors = array_merge($errors, $formaterrors);
        }

        return $errors;
    }
}

