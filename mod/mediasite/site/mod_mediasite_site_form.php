<?php
require_once(dirname(__FILE__) . '/../../../config.php');
require_once("$CFG->dirroot/lib/formslib.php");
require_once("$CFG->dirroot/mod/mediasite/lib.php");
require_once($CFG->dirroot.'/mod/mediasite/basiclti_mediasite_lib.php');
require_once("$CFG->dirroot/mod/mediasite/mediasitesite.php");

defined('MOODLE_INTERNAL') || die();

class mod_mediasite_site_form extends \moodleform {
    private $siteToEdit = null;
    private $navInstalled = null;
    function __construct(Sonicfoundry\MediasiteSite $site = null) {
        $this->siteToEdit = $site;
        parent::__construct();
    }
    function definition() {
        $mform    =& $this->_form;
        $maxbytes = 100000;
        $this->navInstalled = $this->is_navigation_installed();

        $myMediasitePlacementOptions = array(
            mediasite_menu_placement::SITE_PAGES => get_string('my_mediasite_site_pages', 'mediasite'),
            mediasite_menu_placement::COURSE_MENU => get_string('my_mediasite_course_menu', 'mediasite')
            );

        global $CFG;
        // only show the optional states in versions of Moodle that support displaying
        // the course administration "Mediasite Settings" link
        if ($CFG->version >= 2015051100) {
            $mediasite7CoursesModes = array(
                0 => get_string('Disabled', 'mediasite'),
                1 => get_string('Opt-In', 'mediasite'),
                2 => get_string('Opt-Out', 'mediasite'),
                3 => get_string('Always', 'mediasite')
                );
        } else {
            $mediasite7CoursesModes = array(
                0 => get_string('Disabled', 'mediasite'),
                3 => get_string('Always', 'mediasite')
                );
        }
        $mform->addElement('html', '<h2>'.get_string('mediasite_server_list', 'mediasite').'</h2>');

//-------------------------------------------------------------------------------
//        $mform->addElement('header', 'siteheader', get_string('siteheader', 'mediasite'));
//-------------------------------------------------------------------------------
        $mform->addElement('text', 'sitename', get_string('sitename', 'mediasite'), array('class' => 'sofo-site-name'));
        $mform->setType('sitename', PARAM_TEXT);

        $mform->addElement('text', 'siteurl', get_string('serverurl', 'mediasite'), array('class' => 'sofo-site-url'));
        $mform->setType('siteurl', PARAM_TEXT);

        $mform->addElement('text', 'sitelti_consumer_key', get_string('lti_consumer_key', 'mediasite'), array('class' => 'sofo-lti_consumer_key'));
        $mform->setType('sitelti_consumer_key', PARAM_TEXT);

        $mform->addElement('passwordunmask', 'sitelti_consumer_secret', get_string('lti_consumer_secret', 'mediasite'), array('class' => 'sofo-lti_consumer_secret'));
        $mform->setType('sitelti_consumer_secret', PARAM_TEXT);

        $mform->addElement('textarea', 'sitelti_custom_parameters', get_string('lti_custom_parameters', 'mediasite'), array('class' => 'sofo-site-lti-custom-parameters'));
        $mform->setType('sitelti_custom_parameters', PARAM_TEXT);

        $mform->addElement('advcheckbox', 'lti_embed_type_presentation_link', get_string('allowed_lti_presentation_embed_types', 'mediasite'), get_string(Sonicfoundry\MediasiteEmbedFormatTypes::PRESENTATION_LINK, 'mediasite'), array('group' => 1), array(0, Sonicfoundry\MediasiteEmbedFormatValues::PRESENTATION_LINK));
        $mform->setType('lti_embed_type_presentation_link', PARAM_INT);

        $mform->addElement('advcheckbox', 'lti_embed_type_thumbnail', null, get_string(Sonicfoundry\MediasiteEmbedFormatTypes::THUMBNAIL, 'mediasite'), array('group' => 1), array(0, Sonicfoundry\MediasiteEmbedFormatValues::THUMBNAIL));
        $mform->setType('lti_embed_type_thumbnail', PARAM_INT);

        $mform->addElement('advcheckbox', 'lti_embed_type_player_only', null, get_string(Sonicfoundry\MediasiteEmbedFormatTypes::PLAYER_ONLY, 'mediasite'), array('group' => 1), array(0, Sonicfoundry\MediasiteEmbedFormatValues::PLAYER_ONLY));
        $mform->setType('lti_embed_type_player_only', PARAM_INT);

        $mform->addElement('advcheckbox', 'lti_embed_type_abstract_only', null, get_string(Sonicfoundry\MediasiteEmbedFormatTypes::ABSTRACT_ONLY, 'mediasite'), array('group' => 1), array(0, Sonicfoundry\MediasiteEmbedFormatValues::ABSTRACT_ONLY));
        $mform->setType('lti_embed_type_abstract_only', PARAM_INT);

        $mform->addElement('advcheckbox', 'lti_embed_type_abstract_plus_player', null, get_string(Sonicfoundry\MediasiteEmbedFormatTypes::ABSTRACT_PLUS_PLAYER, 'mediasite'), array('group' => 1), array(0, Sonicfoundry\MediasiteEmbedFormatValues::ABSTRACT_PLUS_PLAYER));
        $mform->setType('lti_embed_type_abstract_plus_player', PARAM_INT);

        $mform->addElement('advcheckbox', 'lti_embed_type_link', get_string('allowed_lti_catalog_embed_types', 'mediasite'), get_string(Sonicfoundry\MediasiteEmbedFormatTypes::LINK, 'mediasite'), array('group' => 1), array(0, Sonicfoundry\MediasiteEmbedFormatValues::LINK));
        $mform->setType('lti_embed_type_link', PARAM_INT);

        $mform->addElement('advcheckbox', 'lti_embed_type_embed', null, get_string(Sonicfoundry\MediasiteEmbedFormatTypes::EMBED, 'mediasite'), array('group' => 1), array(0, Sonicfoundry\MediasiteEmbedFormatValues::EMBED));
        $mform->setType('lti_embed_type_embed', PARAM_INT);

        $mform->addElement('header', 'mediasite_integration_catalog_header', get_string('mediasite_integration_catalog_header', 'mediasite'));

        if ($this->navInstalled) {
            $mform->addElement('select', 'show_integration_catalog', get_string('show_integration_catalog', 'mediasite'), $mediasite7CoursesModes);
            $mform->setType('show_integration_catalog', PARAM_INT);

            $mform->addElement('text', 'integration_catalog_title', get_string('integration_catalog_title', 'mediasite'), array('class' => 'sofo-site-integration-catalog-title'));
            $mform->setType('integration_catalog_title', PARAM_TEXT);
            $mform->setDefault('integration_catalog_title', 'Mediasite Catalog');

            $mform->addElement('advcheckbox', 'openpopup_integration_catalog', get_string('openpopup_integration_catalog', 'mediasite'), get_string('openpopup_integration_catalog_postfix', 'mediasite'), null, array(0, 1));
            $mform->setType('openpopup_integration_catalog', PARAM_INT);
        } else {
            // SHOW WARNING
            $mform->addElement('static', 'integration_catalog_not_enabled', get_string('feature_not_enabled', 'mediasite'), get_string('integration_catalog_not_enabled', 'mediasite'));        }

        $mform->addElement('header', 'my_mediasite_header', get_string('my_mediasite_header', 'mediasite'));

        if ($this->navInstalled) {
            $mform->addElement('advcheckbox', 'show_my_mediasite', get_string('show_my_mediasite', 'mediasite'), get_string('show_my_mediasite_postfix', 'mediasite'), null, array(0, 1));
            $mform->setType('show_my_mediasite', PARAM_INT);

            $mform->addElement('text', 'my_mediasite_title', get_string('my_mediasite_title', 'mediasite'), array('class' => 'sofo-site-my-mediasite-title'));
            $mform->setType('my_mediasite_title', PARAM_TEXT);
            $mform->setDefault('my_mediasite_title', 'My Mediasite');

            $mform->addElement('select', 'my_mediasite_placement', get_string('my_mediasite_placement', 'mediasite'), $myMediasitePlacementOptions);
            $mform->setType('my_mediasite_placement', PARAM_INT);

            $mform->addElement('hidden', 'my_mediasite_placement_hint', get_string('my_mediasite_placement_hint', 'mediasite'));
            $mform->setType('my_mediasite_placement_hint', PARAM_TEXT);

            $mform->addElement('advcheckbox', 'openaspopup_my_mediasite', get_string('openaspopup_my_mediasite', 'mediasite'), get_string('openaspopup_my_mediasite_postfix', 'mediasite'), null, array(0, 1));
            $mform->setType('openaspopup_my_mediasite', PARAM_INT);
        } else {
            // SHOW WARNING
            $mform->addElement('static', 'my_mediasite_not_enabled', get_string('feature_not_enabled', 'mediasite'), get_string('my_mediasite_not_enabled', 'mediasite'));
        }

        $mform->closeHeaderBefore('lti_debug_launch');

        $mform->addElement('advcheckbox', 'lti_debug_launch', get_string('lti_debug_launch', 'mediasite'), get_string('lti_debug_launch_postfix', 'mediasite'), null, array(0, 1));
        $mform->setType('lti_debug_launch', PARAM_INT);

        if (is_null($this->siteToEdit)) {
            // need the add buttons
            $mform->addElement('hidden', 'site', 0);
            $mform->setType('site', PARAM_INT);

            $mform->setDefault('lti_embed_type_thumbnail', Sonicfoundry\MediasiteEmbedFormatValues::THUMBNAIL);
            $mform->setDefault('lti_embed_type_abstract_only', Sonicfoundry\MediasiteEmbedFormatValues::ABSTRACT_ONLY);
            $mform->setDefault('lti_embed_type_abstract_plus_player', Sonicfoundry\MediasiteEmbedFormatValues::ABSTRACT_PLUS_PLAYER);
            $mform->setDefault('lti_embed_type_link', Sonicfoundry\MediasiteEmbedFormatValues::LINK);
            $mform->setDefault('lti_embed_type_embed', Sonicfoundry\MediasiteEmbedFormatValues::EMBED);
            $mform->setDefault('lti_embed_type_presentation_link', Sonicfoundry\MediasiteEmbedFormatValues::PRESENTATION_LINK);
            $mform->setDefault('lti_embed_type_player_only', Sonicfoundry\MediasiteEmbedFormatValues::PLAYER_ONLY);

            $this->add_action_buttons(TRUE, get_string('siteaddbuttonlabel', 'mediasite'));
        } else {
            $mform->setDefault('sitename',$this->siteToEdit->get_sitename());
            $mform->setDefault('siteurl',$this->siteToEdit->get_endpoint());
            $mform->setDefault('sitelti_consumer_key',$this->siteToEdit->get_lti_consumer_key());
            $mform->setDefault('sitelti_consumer_secret',$this->siteToEdit->get_lti_consumer_secret());
            $mform->setDefault('sitelti_custom_parameters',$this->siteToEdit->get_lti_custom_parameters());
            if ($this->navInstalled) {
                $mform->setDefault('show_integration_catalog', $this->siteToEdit->get_show_integration_catalog());
                $mform->setDefault('integration_catalog_title', $this->siteToEdit->get_integration_catalog_title());
                $mform->setDefault('openpopup_integration_catalog', $this->siteToEdit->get_openpopup_integration_catalog());
                $mform->setDefault('show_my_mediasite', $this->siteToEdit->get_show_my_mediasite());
                $mform->setDefault('my_mediasite_title', $this->siteToEdit->get_my_mediasite_title());
                $mform->setDefault('my_mediasite_placement', $this->siteToEdit->get_my_mediasite_placement());
                $mform->setDefault('openaspopup_my_mediasite', $this->siteToEdit->get_openaspopup_my_mediasite());
            }
            $mform->setDefault('lti_debug_launch', $this->siteToEdit->get_lti_debug_launch());
            $mform->setDefault('lti_embed_type_thumbnail', $this->siteToEdit->get_lti_embed_type_thumbnail());
            $mform->setDefault('lti_embed_type_abstract_only', $this->siteToEdit->get_lti_embed_type_abstract_only());
            $mform->setDefault('lti_embed_type_abstract_plus_player', $this->siteToEdit->get_lti_embed_type_abstract_plus_player());
            $mform->setDefault('lti_embed_type_link', $this->siteToEdit->get_lti_embed_type_link());
            $mform->setDefault('lti_embed_type_embed', $this->siteToEdit->get_lti_embed_type_embed());
            $mform->setDefault('lti_embed_type_presentation_link', $this->siteToEdit->get_lti_embed_type_presentation_link());
            $mform->setDefault('lti_embed_type_player_only', $this->siteToEdit->get_lti_embed_type_player_only());

            $mform->addElement('hidden', 'site', $this->siteToEdit->get_siteid());
            $mform->setType('site', PARAM_INT);
            $this->add_action_buttons(TRUE, get_string('savechangebutton', 'mediasite') );
        }
    }
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $this->navInstalled = $this->is_navigation_installed();

        if(isset($data['sitename']) && strlen($data['sitename']) > 0) {
            if(strlen($data['sitename']) > 254) {
                $errors['sitename'] = get_string('longsitename', 'mediasite');
            }
        } else {
            $errors['sitename'] = get_string('requiredsitename', 'mediasite');
        }
        if(isset($data['sitelti_consumer_key']) && strlen($data['sitelti_consumer_key']) > 3) {
            if(strlen($data['sitelti_consumer_key']) > 254) {
                $errors['sitelti_consumer_key'] = get_string('longsitelti_consumer_key', 'mediasite');
            }
        } else {
            $errors['sitelti_consumer_key'] = get_string('requiredsitelti_consumer_key', 'mediasite');
        }
        if(isset($data['sitelti_consumer_secret']) && strlen($data['sitelti_consumer_secret']) > 3) {
            if(strlen($data['sitelti_consumer_secret']) > 254) {
                $errors['sitelti_consumer_secret'] = get_string('longsitelti_consumer_secret', 'mediasite');
            }
        } else {
            $errors['sitelti_consumer_secret'] = get_string('requiredsitelti_consumer_secret', 'mediasite');
        }

        $url = $data['siteurl'];
        if(!preg_match('%\bhttps?:\/\/%si',$url)) {
            $url = 'http://'.$url;
        }
        if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",$url))
        {
            $errors['siteurl'] = get_string('invalidURL', 'mediasite');
        } else  {
            // BUG43973:    MOODLE: plugin config page URL field doesn't correctly test url format
            $reachableError = $this->is_url_reachable($url);
            if ($reachableError != null) {
                $errors['siteurl'] = $reachableError;
            }
        }

        if(isset($data['sitename'])) {
            global $DB;
            $sites = $DB->get_records('mediasite_sites', null, '', 'sitename');
            foreach($sites as $site) {
                if(strtolower($data['sitename']) == strtolower($site->sitename)) {
                    if(is_null($this->siteToEdit)) {
                        $errors['sitename'] = get_string('duplicatesitename', 'mediasite', $data['sitename']);
                        break;
                    } elseif($data['sitename'] != $this->siteToEdit->get_sitename()) {
                        $errors['sitename'] = get_string('duplicatesitename', 'mediasite', $data['sitename']);
                        break;
                    }
                }
            }
            if(strlen($data['sitename']) > 254) {
                $errors['sitename'] = get_string('longsitename', 'mediasite');
            }
        } else {
            $errors['sitename'] = get_string('requiredsitename', 'mediasite');
        }

        // at least one presentation embed type needs to be selected
        if ($data['lti_embed_type_thumbnail'] > 0
            || $data['lti_embed_type_abstract_only'] > 0
            || $data['lti_embed_type_abstract_plus_player'] > 0
            || $data['lti_embed_type_player_only'] > 0
            || $data['lti_embed_type_presentation_link'] > 0) {
            // sweet
        } else {
            $errors['lti_embed_type_presentation_link'] = get_string('lti_embed_type_presentation_required', 'mediasite');
        }

        // at least one catalog embed type needs to be selected
        if ($data['lti_embed_type_link'] > 0
            || $data['lti_embed_type_embed'] > 0) {
            // sweet
        } else {
            $errors['lti_embed_type_link'] = get_string('lti_embed_type_catalog_required', 'mediasite');
        }

        if ($this->navInstalled) {
            if ($data['show_integration_catalog']) {
                // check the link text
                if (!isset($data['integration_catalog_title']) || (strlen($data['integration_catalog_title']) === 0)) {
                    $errors['integration_catalog_title'] = get_string('integration_catalog_title_required', 'mediasite');
                }
            }
            if ($data['show_my_mediasite']) {
                // check the link text
                if (!isset($data['my_mediasite_title']) || (strlen($data['my_mediasite_title']) === 0)) {
                    $errors['my_mediasite_title'] = get_string('my_mediasite_title_required', 'mediasite');
                }
            }
        }
        
        if(count($errors) > 0) {
            return $errors;
        }

        $matches = array();
        return $errors;
    }

    function is_navigation_installed() {
        return is_local_mediasite_courses_installed();
    }

    function is_url_reachable($url) {
        $headers = @get_headers($url);
        if ($headers) {
            // really expecting a 302 . . . are there other headers that make sense?
            if (strpos($headers[0], '302') <= 0) {
                return get_string('invalidURL_response', 'mediasite', $headers[0]);
            }
        } else {
            // OK so getting the headers didn't work . . . now what?
            // for now, don't raise an error . . . 
            //     @get_headers isn't reliable
            //     curl is not always available
        }
        return null;
    }
}