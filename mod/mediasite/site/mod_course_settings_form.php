<?php
namespace Sonicfoundry;

global $CFG;
require_once("$CFG->dirroot/lib/formslib.php");
require_once("$CFG->dirroot/mod/mediasite/lib.php");
require_once("$CFG->dirroot/mod/mediasite/mediasitesite.php");

defined('MOODLE_INTERNAL') || die();

class mod_course_settings_form extends \moodleform {
    private $courseid = null;
    function __construct($courseid) {
    	global $PAGE;
        $this->courseid = $courseid;
		parent::__construct();
		$PAGE->requires->js(new \moodle_url('/mod/mediasite/js/course_settings.js'), true);
    }
    function definition() {
        global $DB,$OUTPUT;

        $mform = $this->_form;
        $context = \context_course::instance($this->courseid); 

		if (!(has_capability('mod/mediasite:overridedefaults', $context))) {
			return;
		}

        $courseConfig = $DB->get_record('mediasite_course_config', array('course' => $this->courseid));
        $siteList = $DB->get_records('mediasite_sites');
        $defaults = $DB->get_record('mediasite_config', array());

        $mform->addElement('header', 'siteselectionheader', get_string('siteselectionheader', 'mediasite'));
	
	    // build a json object that has the Mediasite 7 Courses settings
	    $json = '';
        $sitenames = array();
        foreach ($siteList as $site) {
            $sitenames[$site->id] = $site->sitename;
	    	if ($json != '') {
	    		$json = $json.', ';
	    	}
	    	$json = $json.'{"id":"'.$site->id.'", "name":"'.htmlspecialchars($site->sitename).'", "coursesTitle":"'.htmlspecialchars($site->integration_catalog_title).'", "showIntegrationCatalog":"'.$site->show_integration_catalog.'"}';
        }
	    $json = '{"sites": ['.$json.']}';

	    if (!$courseConfig) {
	    	$currentSiteId = $defaults->siteid;
	    } else {
	    	$currentSiteId = $courseConfig->mediasite_site;
	    }

	    $course7LinkText = $DB->get_field('mediasite_sites', 'integration_catalog_title', array('id' => $currentSiteId));

	    $sitedropdown = $mform->addElement('select', 'mediasite_site', get_string('sitenames', 'mediasite'), $sitenames, array('id' => 'id_siteid', 'onchange' => 'javascript:siteChange(this, '.$json.');'));

	    $coursesMode = $mform->addElement('advcheckbox', 'mediasite_courses_enabled', get_string('mediasite_courses_enabled', 'mediasite'), $course7LinkText, null, array(0, 1));

        if (!$courseConfig) {
	        $sitedropdown->setSelected($defaults->siteid);
	        // what is the value of the show_integration_catalog for the default course
	        $show = $DB->get_field('mediasite_sites', 'show_integration_catalog', array('id' => $defaults->siteid));
	        //$show = (($show * 1.0) > 1 ? 1 : 0);
	        $mform->setDefault('mediasite_courses_enabled', ($show > 1));
	    } else {
	    	$sitedropdown->setSelected($courseConfig->mediasite_site);
	    	$mform->setDefault('mediasite_courses_enabled', $courseConfig->mediasite_courses_enabled);
	    }

        $mform->addElement('hidden', 'id', $this->courseid);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'mediasite_course_config_id', $courseConfig ? $courseConfig->id : '0');
        $mform->setType('mediasite_course_config_id', PARAM_INT);

        $this->add_action_buttons(TRUE, get_string('savechangebutton', 'mediasite') );

    }
}
?>