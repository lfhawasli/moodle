<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once("$CFG->dirroot/course/moodleform_mod.php");
require_once("$CFG->dirroot/mod/mediasite/locallib.php");
require_once("$CFG->dirroot/mod/mediasite/mediasitesite.php");
require_once("$CFG->dirroot/mod/mediasite/exceptions.php");

defined('MOODLE_INTERNAL') || die();

class mod_mediasite_mod_form extends moodleform_mod {
    function __construct($data, $section, $cm, $course) {

        parent::__construct($data, $section, $cm, $course);

		global $CFG,$DB,$PAGE;
        $sites = $DB->get_records('mediasite_sites');
        $config = $DB->get_record('mediasite_config', array());
        if(!$config || is_null($config) ||
           !$sites  || is_null($sites) || count($sites) < 1) {
            // Go home
            print_error(get_string('incompleteconfiguration', 'mediasite'));
            redirect($CFG->wwwroot);
        }
        $PAGE->requires->js(new moodle_url('/mod/mediasite/js/basiclti_callback.js'), true);
    }

    function definition()
    {
        $mform = $this->_form;
        $cm = $this->_cm;

//-------------------------------------------------------------------------------

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('hidden', 'gate');
        $mform->setType('gate', PARAM_INT);
        $mform->setDefault('gate', 0);

        global $CFG,$COURSE;

        if (!is_object($cm) || !isset($cm->id) || !($cm->id > 0)) {
            if (count($this->mediasite_get_lti_sites(false)) > 0) {
                $ltiUrl = "$CFG->wwwroot/mod/mediasite/lti_site_selection.php?course=". strval($COURSE->id)."&cm=".strval($this->current->instance);
                $mform->addElement('html', '<div id="mediasite_lti_content"><iframe id="mediasite_lti_content_iframe" src="'.$ltiUrl.'"></iframe></div>');

            } else {
                // display an error
                throw new moodle_exception('generalexceptionmessage', 'error', '', 'Plugin configuration is incomplete. Please contact the administrator and request sites be added to the Mediasite Activity Plugin.');
            }
        }

        if (isset($cm->id)) {
            global $DB;
            $mediasite = $DB->get_record("mediasite", array("id" => $cm->instance));
            $site = new Sonicfoundry\MediasiteSite($DB->get_record("mediasite_sites", array("id" => $mediasite->siteid)));
            $supportedEmbedTypes = $site->get_embed_capabilities(false, $mediasite->resourcetype);
            $formOptions = array();
            foreach($supportedEmbedTypes as $s) {
                $formOptions[$s->formatType] = get_string($s->formatType, 'mediasite');
            }
            $mform->addElement('select', 'mode', get_string('mode', 'mediasite'), $formOptions, array('onchange' => 'javascript:toggleEmbedModeChange(this.value);'));
            $mform->setType('mode', PARAM_TEXT);
            $mform->setDefault('mode', $mediasite->mode);
            $mform->addRule('mode', null, 'required', null, 'server');

            $tags = str_replace('~!~', ', ', $mediasite->tags);
            $presenters = str_replace('~!~', '\n\n', $mediasite->presenters);
        
            $mform->addElement('html', '<script type="text/javascript">setTimeout(function () { toggleEmbedModeChange("'.$mediasite->mode.'") }, 50);</script>');
        
        } else {
            $mform->addElement('hidden', 'mode', '', array('id' => 'id_mode'));
            $mform->setType('mode', PARAM_TEXT);
        }


        // the following fields are using css classes in conjunction with javascript to toggle their 
        // visibility as the display mode changes.
        $mform->addElement('text', 'name', get_string('resourcetitle', 'mediasite'), array('size' => '97', 'class' => 'sofo-embed sofo-embed-type-PresentationLink  sofo-embed-type-PlayerOnly sofo-embed-type-MetadataLight sofo-embed-type-MetadataOnly sofo-embed-type-MetadataPlusPlayer sofo-embed-type-BasicLTI sofo-embed-type-iFrame'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'server');


        $mform->addElement('textarea', 'description', get_string('description', 'mediasite'), array('wrap' => "virtual",
                                                                                                    'rows' => 20, 
                                                                                                    'cols' => 100,
                                                                                                    'class' => 'sofo-embed sofo-embed-type-MetadataOnly sofo-embed-type-MetadataPlusPlayer sofo-embed-type-iFrame' ));
        $mform->setType('description', PARAM_TEXT);

        $mform->addElement('textarea', 'presenters_display', get_string('presenters', 'mediasite'), array('wrap' => "virtual",
                                                                                              'rows' => 15, 
                                                                                              'cols' => 100,
                                                                                              'class' => 'sofo-embed sofo-readonly sofo-embed-type-MetadataOnly sofo-embed-type-MetadataPlusPlayer',
                                                                                              'onfocus' => 'this.blur();'));
        $mform->setType('presenters_display', PARAM_TEXT);

        $mform->addElement('textarea', 'tags_display', get_string('tags', 'mediasite'), array('class' => 'sofo-embed sofo-readonly sofo-embed-type-MetadataOnly sofo-embed-type-MetadataPlusPlayer',
                                                                                  'wrap' => "virtual",
                                                                                  'rows' => 3, 
                                                                                  'cols' => 100,
                                                                                  'onfocus' => 'this.blur();'));
        $mform->setType('tags_display', PARAM_TEXT);

        // $mform->addElement('advcheckbox', 'showdescription', get_string('showdescription', 'mediasite'), null, null, array(0, 1));
        $mform->addElement('hidden', 'showdescription');
        $mform->setType('showdescription', PARAM_INT);
        $mform->setDefault('showdescription', 1);

        $context = context_course::instance($COURSE->id);

        if(has_capability('mod/mediasite:overridedefaults', $context)) {
            $mform->addElement('advcheckbox', 'openaspopup', get_string('openaspopup', 'mediasite'), null);
            $mform->setDefault('openaspopup', 1);
        } else {
            $mform->addElement('hidden', 'openaspopup');
            $mform->setType('openaspopup', PARAM_INT);
        }

        global $DB;
        $config = $DB->get_record('mediasite_config', array());
        if($config) {
            $mform->setDefault('openaspopup', $config->openaspopup);
        }

        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();

        // add standard buttons, common to all modules
        $this->add_action_buttons();

        $mform->closeHeaderBefore('siteid');

        $mform->addElement('hidden', 'siteid', -1, array('id' => 'id_siteid'));
        $mform->setType('siteid', PARAM_INT);

        $mform->addElement('hidden', 'resourcetype', '', array('id' => 'id_resourcetype'));
        $mform->setType('resourcetype', PARAM_TEXT);

        $mform->addElement('hidden', 'resourceid', '', array('id' => 'id_resourceid'));
        $mform->setType('resourceid', PARAM_TEXT);

        $mform->addElement('hidden', 'recorddateutc', '', array('id' => 'id_recorddateutc'));
        $mform->setType('recorddateutc', PARAM_TEXT);

        $mform->addElement('hidden', 'presenters', '', array('id' => 'id_presenters'));
        $mform->setType('presenters', PARAM_TEXT);

        $mform->addElement('hidden', 'tags', '', array('id' => 'id_tags'));
        $mform->setType('tags', PARAM_TEXT);

        $mform->addElement('hidden', 'launchurl', '', array('id' => 'id_launchurl'));
        $mform->setType('launchurl', PARAM_TEXT);

        global $COURSE,$CFG;

        if(method_exists($mform,'setExpanded')) {
            $mform->setExpanded('modstandardelshdr', false);
        }

    }


    function mediasite_get_editor_options($context) {

    }
    function definition_after_data()
    {
        //$users = get_enrolled_users(context_course::instance($this->_courseid));
        //$roles = get role_archetypes();
        //$roles = get_all_roles();
        //$roles = get_user_roles(context_course::instance($this->_courseid));
        parent::definition_after_data();
        $mform = $this->_form;
        $cm = $this->_cm;
        if (isset($cm->id)) {
            global $DB;
            $mediasite = $DB->get_record("mediasite", array("id" => $cm->instance));

            $tags = str_replace('~!~', ', ', $mediasite->tags);
            $presenters = str_replace('~!~', "\r\n\r\n", $mediasite->presenters);

            $mform->setDefault('tags_display', $tags);
            $mform->setDefault('presenters_display', $presenters);
        }
    }

    function validation($data, $files)
    {
        $errors = parent::validation($data, $files);
        global $USER;

        if (mediasite_has_value($data['resourceid']) && mediasite_has_value($data['siteid']) && mediasite_has_value($data['resourcetype']) && mediasite_has_value($data['mode'])) {
            // good, proceed
        } else {
            // bad, blow up
            $errors['name'] = get_string('form_data_invalid', 'mediasite');
        }
        try {
            //validate the current user has access to the selected resource
            $valid = mediasite_check_resource_permission($data['resourceid'], $data['resourcetype'], $USER->username);
            if (!$valid) {
                $errors['resourceid'] = get_string('notauthorized', 'mediasite');
            }
        } catch (\Sonicfoundry\SonicfoundryException $se) {
            $errors['resourceid'] = $se->getMessage();
        } catch (Exception $e) {
            $errors['resourceid'] = $e->getMessage();
        }

        return $errors;
    }

    function mediasite_get_lti_sites($onlyShowIntegrationCatalogEnabled = false) {
        global $DB;
        if ($onlyShowIntegrationCatalogEnabled) {
            $records = $DB->get_records('mediasite_sites', array('show_integration_catalog' => true));
        } else {
            $records = $DB->get_records('mediasite_sites');
        }
        return $records;
    }
}

?>
