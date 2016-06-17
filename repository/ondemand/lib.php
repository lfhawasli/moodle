<?php
// This file is part of Moodle - http://moodle.org/
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
 * This plugin is used to access ondemand files
 *
 * @since Moodle 2.0
 * @package    repository_ondemand
 * @Author    Pramod Ubbala (AGS -> Infobase)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/repository/lib.php');

class repository_ondemand extends repository {

    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array()) {
        parent::__construct($repositoryid, $context, $options);
        $this->keyword = optional_param('ondemand_keyword', '', PARAM_RAW);
        $this->licensekey = trim(get_config('ondemand', 'licensekey'));
        $this->launchUrl  = trim(get_config('ondemand', 'launchURl'));
    }

    /**
     * Get a list of links
     * @return array
     */
    public function get_listing($path = '', $page = '') {
        global $COURSE;
        $callbackurl = new moodle_url('/repository/ondemand/callback.php', array('repo_id'=>$this->id));

        $tempURl = (string)$this->launchUrl;
        $templicensekey = (string)$this->licensekey;
        
        $url =  $tempURl
                . '?method=lms'
                . '&returnurl='.urlencode($callbackurl)
                . '&returnprefix=tle'
                . '&courseId='.urlencode($COURSE->idnumber)
                . '&courseCode='.urlencode($COURSE->shortname)
                . '&action=searchThin'
                . '&oauth_consumer_key=' . urlencode($templicensekey) 
                . '&ConsumerFamily=moodle';

        $manageurl = $this->launchUrl;

        $list = array();
        $list['object'] = array('video');
        $list['object']['type'] = 'text/html';
        $list['object']['src'] = $url;
        $list['nologin']  = true;
        $list['nosearch'] = true;
        $list['norefresh'] = true;
        $list['manage'] = $manageurl;
        return $list;
    }
    
    /**
     * Names of the plugin settings
     *
     * @return array
     */
    public static function get_type_option_names() {
        return array('licensekey','launchURl', 'pluginname');
    }

    /**
     * Add Plugin settings input to Moodle form
     *
     * @param object $mform
     */
    public static function type_config_form($mform, $classname = 'repository') {
        parent::type_config_form($mform);
        $licensekey = get_config('ondemand', 'licensekey');
        $launchURl  = get_config('ondemand', 'launchURl');
        
        if (empty($licensekey)) {
            $licensekey = '';
        }
        
         if (empty($launchURl)) {
            $launchURl = '';
        }

        $strrequired = get_string('required');
        
        $mform->addElement('text', 'launchURl', get_string('launchURl', 'repository_ondemand'), array('value'=>$launchURl,'size' => '500'));
        $mform->setType('launchURl', PARAM_URL);
        $mform->addRule('launchURl', $strrequired, 'required', null, 'client');
        
        $mform->addElement('text', 'licensekey', get_string('licensekey', 'repository_ondemand'), array('value'=>$licensekey,'size' => '500'));
        $mform->setType('licensekey', PARAM_RAW_TRIMMED);
        $mform->addRule('licensekey', $strrequired, 'required', null, 'client');
    }  
    
    public static function type_form_validation($mform, $data, $errors) {
    $templaunchUrl = $data['launchURl'];    
     
    if(!filter_var($templaunchUrl, FILTER_VALIDATE_URL))
        {
          $errors['launchURl'] = "Please Enter a Valid URL.";
        }
    else
        {
             if(     strpos(strtolower($templaunchUrl), 'lti.films.com') === false 
                 and strpos(strtolower($templaunchUrl), 'localhost')    === false)
                {
                  $errors['launchURl'] = "Please Enter a Valid URL.";
                }
            else
                {
                  $errors = array();  
                }
        }
    
        return $errors;
    }
    
    /**
     * Support external link only
     *
     * @return int
     */
    public function supported_returntypes() {
        return FILE_EXTERNAL;
    }
    
    public function supported_filetypes() {
        return array('video');
    }

    /**
     * Is this repository accessing private data?
     *
     * @return bool
     */
    public function contains_private_data() {
        return false;
    }
}

