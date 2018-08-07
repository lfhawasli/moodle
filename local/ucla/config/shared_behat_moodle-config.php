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
 *  This configuration file has been preconfigured to set certain variables
 *  such that launching and upgrades will run as smoothly as possible.
 *
 *  Currently, the plan is to symbolically link this file as such:
 *  moodle/config.php -> moodle/local/ucla/config/<this file>
 *
 *  The original configuration file should not be used, as it does not have
 *  any capability of saying that another configuration file can be
 *  included before starting the Moodle session.
 *
 *  If you want configurations to be not within revisioning, then place
 *  your secrets @ moodle/config_private.php.
 *
 */

unset($CFG);
global $CFG;

$CFG = new stdClass();

$CFG->dbtype    = 'mysqli';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = '';
$CFG->dbuser    = '';
$CFG->dbpass    = '';
$CFG->prefix    = 'mdl_';
$CFG->dboptions['dbpersist'] = 0;
$CFG->dboptions['dbsocket']  = 1;
$CFG->dboptions['dbcollation'] = 'utf8mb4_unicode_ci';

$CFG->wwwroot  = '';
$CFG->dataroot = '';

// This determines what the admin folder is called.
$CFG->admin    = 'admin';

// This is directory permissions for newly created directories.
$CFG->directorypermissions = 0777;

// This should never change after the first install, or else any special
// logins using the Moodle login will not work.
$CFG->passwordsaltmain = '';

// Registrar.
$CFG->registrar_dbtype = 'odbc_mssql';
$CFG->registrar_dbhost = '';
$CFG->registrar_dbuser = '';
$CFG->registrar_dbpass = '';
$CFG->registrar_dbname = 'srdb';
$CFG->registrar_dbencoding = 'ISO-8859-1';

// Format and browseby and anything else that requires instructors to be
// displayed, we need to determine which roles should be displayed.
$CFG->instructor_levels_roles['Instructor'] = array('editinginstructor', 'ta_instructor');
$CFG->instructor_levels_roles['Teaching Assistant'] = array('ta', 'ta_admin');
$CFG->instructor_levels_roles['Student Facilitator'] = array('studentfacilitator');

// My Sites CCLE-2810 - Term limiting.
$CFG->forced_plugin_settings['local_ucla']['student_access_ends_week'] = 3;
$CFG->forced_plugin_settings['local_ucla']['oldest_available_term'] = '08S';

// Browseby CCLE-2894.
$CFG->forced_plugin_settings['block_ucla_browseby']['use_local_courses'] = true;
$CFG->forced_plugin_settings['block_ucla_browseby']['ignore_coursenum'] = '194,295,296,375';
$CFG->forced_plugin_settings['block_ucla_browseby']['allow_acttypes'] = 'CLI,LEC,SEM,LAB,FLD,ACT,STU,REC,RGP';

// Course Requestor.
$CFG->forced_plugin_settings['tool_uclacourserequestor']['mailinst_default'] = false;
$CFG->forced_plugin_settings['tool_uclacourserequestor']['nourlupdate_default'] = true;
$CFG->forced_plugin_settings['tool_uclacourserequestor']['nourlupdate_hide'] = true;

// Course Creator.
$CFG->forced_plugin_settings['tool_uclacoursecreator']['course_creator_email'] = 'ccle-operations@lists.ucla.edu';
$CFG->forced_plugin_settings['tool_uclacoursecreator']['email_template_dir'] = '';
$CFG->forced_plugin_settings['tool_uclacoursecreator']['make_division_categories'] = true;

// MyUCLA url updater.
$CFG->forced_plugin_settings['tool_myucla_url']['url_service'] = 'https://m2test.ccle.ucla.edu/rex/myucla_url_updater/update.php';  // test server
$CFG->forced_plugin_settings['tool_myucla_url']['user_name'] = 'CCLE Admin';   // name for registering URL with My.UCLA
$CFG->forced_plugin_settings['tool_myucla_url']['user_email'] = 'ccle@ucla.edu';  // email for registering URL with My.UCLA
$CFG->forced_plugin_settings['tool_myucla_url']['override_debugging'] = true;   // test sending MyUCLA urls

// For MyUCLA url updater phpunit tests.
define('MYUCLA_URL_UPDATER_TEST_CONFIG_URL',
        $CFG->forced_plugin_settings['tool_myucla_url']['url_service']);
define('MYUCLA_URL_UPDATER_TEST_CONFIG_NAME',
        $CFG->forced_plugin_settings['tool_myucla_url']['user_name']);
define('MYUCLA_URL_UPDATER_TEST_CONFIG_EMAIL',
        $CFG->forced_plugin_settings['tool_myucla_url']['user_email']);
define('MYUCLA_URL_UPDATER_TEST_CONFIG_OVERRIDE_DEBUGGING',
        $CFG->forced_plugin_settings['tool_myucla_url']['override_debugging']);
define('MYUCLA_URL_UPDATER_TEST_CONFIG_ACCESSDENIED_URL',
        'https://test.ccle.ucla.edu/myucla_url_updater/accessdenied.php');

// Turn off messaging (CCLE-2318 - MESSAGING).
$CFG->messaging = false;

// CCLE-2763 - Use new $CFG->divertallemailsto setting in 1.9 and 2.x
// development/testing environments.
$CFG->divertallemailsto = 'ccle-email-test@lists.ucla.edu';

// CCLE-2306 - HELP SYSTEM BLOCK.
// If using JIRA, jira_user, jira_password, jira_pid should be defined in config_private.php.
$CFG->forced_plugin_settings['block_ucla_help']['jira_endpoint'] = 'https://ucla-ccle.atlassian.net/rest/api/latest/issue';
$CFG->forced_plugin_settings['block_ucla_help']['docs_wiki_url'] = 'https://docs.ccle.ucla.edu/w/';
$CFG->forced_plugin_settings['block_ucla_help']['docs_wiki_api'] = 'https://docs.ccle.ucla.edu/api.php';
$block_ucla_help_support_contacts['System'] = 'support';  // Default.
$CFG->forced_plugin_settings['block_ucla_help']['maxfilesize'] = 10485760;

// CCLE-2301 - COURSE MENU BLOCK.
$CFG->forced_plugin_settings['block_ucla_course_menu']['trimlength'] = 22;

// UCLA Theme settings.
$CFG->forced_plugin_settings['theme_uclashared']['running_environment'] = 'behat';
$CFG->forced_plugin_settings['theme_uclashared']['frontpage_image'] = 'frontpage-image-shared';
$CFG->cachejs = false;

// Enable conditional activities.
$CFG->enableavailability = true;
$CFG->enablecompletion = true;  // Needs to be enabled so that completion
                                // of tasks can be one of the conditions.

// CCLE-2792 - Enable multimedia filters.
// NOTE: You still need to manually set the "Active?" value of the "Multimedia
// plugins" filter at "Site administration > Plugins > Filters > Manage filters".
$CFG->filter_mediaplugin_enable_youtube = true;
$CFG->filter_mediaplugin_enable_vimeo = true;
$CFG->filter_mediaplugin_enable_mp3 = true;
$CFG->filter_mediaplugin_enable_flv = true;
$CFG->filter_mediaplugin_enable_swf = false;    // Security risk if enabled.
$CFG->filter_mediaplugin_enable_html5audio = true;
$CFG->filter_mediaplugin_enable_html5video = true;
$CFG->filter_mediaplugin_enable_qt = true;
$CFG->filter_mediaplugin_enable_wmp = true;
$CFG->filter_mediaplugin_enable_rm = true;

// CCLE-2362 - MyUCLA Gradebook Integration.
$CFG->gradebook_webservice = 'https://stage.cis.ucla.edu/ws/moodleitemgrade/service.svc?wsdl';
// This ID is discountinued, if you need a real gradebook_id, please contact
// the CCLE lead developer and assign it via your config_private.php file.
$CFG->gradebook_id = 1;
$CFG->gradebook_password = '123';
$CFG->gradebook_send_updates = 0;

// CCLE-2810 - My Sites - disallow customized "My Moodle" page.
// Core Behat tests do not expect this.
$CFG->forcedefaultmymoodle = true;

// Enable PHPunit/Behat testing.
$CFG->phpunit_prefix = 'phpu_';
$CFG->behat_prefix = 'bht_';

// Email address to notify in case of system problems.
$CFG->forced_plugin_settings['local_ucla']['admin_email'] = 'ccle-operations@lists.ccle.ucla.edu';

// CCLE-5959 - Disable Web Installations of Plugins.
$CFG->disableupdateautodeploy = 1;

// Site administration > Users > Accounts > User default preferences
$CFG->defaultpreference_autosubscribe = 0;
$CFG->defaultpreference_trackforums = 1;

// Site administration > Users > Permissions > User policies
$CFG->autologinguests = true;
$CFG->showuseridentity = 'idnumber,email';

// Site administration > Courses > Course default settings
$CFG->forced_plugin_settings['moodlecourse']['format'] = 'ucla';
$CFG->forced_plugin_settings['moodlecourse']['maxbytes'] = 2147483648;  // 2GB
// CCLE-2903 - Don't set completion tracking to be course default
$CFG->forced_plugin_settings['moodlecourse']['enablecompletion'] = 0;
// CCLE-7325 - Disable "Course end date enabled by default"
$CFG->forced_plugin_settings['moodlecourse']['courseenddateenabled'] = 0;

// Site administration > Courses > Course request
$CFG->enablecourserequests = 1;

// Site administration > Courses > Backups > General backup defaults
// Commenting this out until following tracker issue is resolved:
// MDL-27886 - backup_general_users forbids all users to backup user data
//$CFG->forced_plugin_settings['backup']['backup_general_users'] = 0;

// Site administration > Grades > General settings
$CFG->recovergradesdefault = 1;
$CFG->gradepointmax = 300;

// Site administration > Language > Language settings
$CFG->langstringcache = false;

// Site administration > Plugins > Activity modules > Book
$CFG->forced_plugin_settings['book']['requiremodintro'] = 0;

// Site administration > Plugins > Activity modules > Folder
$CFG->forced_plugin_settings['folder']['requiremodintro'] = 0;

// Site administration > Plugins > Activity modules > IMS content package
$CFG->forced_plugin_settings['imscp']['requiremodintro'] = 0;

// Site administration > Plugins > Activity modules > Page
$CFG->forced_plugin_settings['page']['requiremodintro'] = 0;
$CFG->forced_plugin_settings['page']['printheading'] = 1;

// Site administration > Plugins > Activity modules > File
$CFG->forced_plugin_settings['resource']['requiremodintro'] = 0;
$CFG->forced_plugin_settings['resource']['printheading'] = 1;

// Site administration > Plugins > Activity modules > Turnitin Assignment (Legacy)
$CFG->turnitin_apiurl = 'https://api.turnitin.com/api.asp';
$CFG->turnitin_studentemail = 0;
$CFG->turnitin_tutoremail = 0;

// Site administration > Plugins > Activity modules > Turnitin Assignment
$CFG->forced_plugin_settings['turnitintooltwo']['useerater'] = 1;
$CFG->forced_plugin_settings['turnitintooltwo']['useanon'] = 1;
$CFG->forced_plugin_settings['turnitintooltwo']['inboxlayout'] = 1;

// Site administration > Plugins > Activity modules > URL
$CFG->forced_plugin_settings['url']['requiremodintro'] = 0;
$CFG->forced_plugin_settings['url']['displayoptions'] = '0,1,2,3,4,5,6';    // allow every option
$CFG->forced_plugin_settings['url']['printheading'] = 1;
$CFG->forced_plugin_settings['url']['display'] = 3; // RESOURCELIB_DISPLAY_NEW

// Site administration > Plugins > Activity modules > Video Annotation
$CFG->tnawebserviceurl = array("http://www.sscnet.ucla.edu/tna/webservice/edge/webservice.php",
                               "http://www.sscnet.ucla.edu/tna/webservice/edge/webservice.php",
                               "http://www.sscnet.ucla.edu/tna/webservice/edge/webservice.php",
                               "http://www.sscnet.ucla.edu/tna/webservice/edge/webservice.php",
                               "http://tvnews.library.ucla.edu/util/webservice.php",
                               "http://newsscape.library.ucla.edu/util/webservice.php",
                               "https://tvnews.sscnet.ucla.edu/webservice/edge/webservice.php");
$CFG->tnapermalinkurl = array("http://www.sscnet.ucla.edu/tna/setesting/video,",
                              "http://www.sscnet.ucla.edu/tna/edge/video,",
                              "http://www.sscnet.ucla.edu/csa/search/video,",
                              "http://dcl.sscnet.ucla.edu/search/video,",
                              "http://tvnews.library.ucla.edu/video,",
                              "http://newsscape.library.ucla.edu/video,",
                              "https://tvnews.sscnet.ucla.edu/edge/video,");
$CFG->tnastreamerurl = "rtmpt://wowza.library.ucla.edu/csa_edge";

// Site administration > Plugins > Assignment plugins > Submission plugins > File submissions
$CFG->forced_plugin_settings['assignsubmission_file']['maxbytes'] = 10485760;

// Site administration > Plugins > Assignment plugins > Feedback plugins > Feedback comments
$CFG->forced_plugin_settings['assignfeedback_comments']['default'] = 1;

// Site administration > Plugins > Assignment plugins > Feedback plugins > File feedback
$CFG->forced_plugin_settings['assignfeedback_file']['default'] = 1;

// Site administration > Plugins > Enrollments > Guest access
$CFG->forced_plugin_settings['enrol_guest']['defaultenrol'] = 1;
// Core Behat tests depend on default setting.
//$CFG->forced_plugin_settings['enrol_guest']['status'] = 0;  // 0 is yes, 1 is no

// Site administration > Plugins > Enrollments > Self enrolment
// Core Behat tests depend on default setting.
//$CFG->forced_plugin_settings['enrol_self']['defaultenrol'] = 0;
$CFG->forced_plugin_settings['enrol_self']['status'] = 1;  // 0 is yes, 1 is no
$CFG->forced_plugin_settings['enrol_self']['sendcoursewelcomemessage'] = 0;

// Site administration > Plugins > Blocks > UCLA bruincast
$CFG->forced_plugin_settings['block_ucla_bruincast']['source_url'] = 'http://www.oid.ucla.edu/help/info/bcastlinks/';
$CFG->forced_plugin_settings['block_ucla_bruincast']['errornotify_email'] = 'ccle-operations@lists.ucla.edu';
$CFG->forced_plugin_settings['block_ucla_bruincast']['quiet_mode'] = 1;

// Site administration > Plugins > Blocks > UCLA library reserves
$CFG->forced_plugin_settings['block_ucla_library_reserves']['source_url'] = 'https://webservices.library.ucla.edu/reserves';

// Site administration > Plugins > Blocks > UCLA video reserves
$CFG->forced_plugin_settings['block_ucla_video_reserves']['sourceurl'] = 'ftp://guest:access270@164.67.141.31//Users/guest/Sites/MEDIA_LINKS.txt';

// Data Source Sync (bruincast, video reserves, library reserves) contact Email
$CFG->forced_plugin_settings['tool_ucladatasourcesync']['contact_email']='ccle-operations@lists.ccle.ucla.edu';

// Site administration > Plugins > Blocks > i>clicker Moodle integrate
$CFG->forced_plugin_settings['block_iclicker']['block_iclicker_notify_emails'] = 'ccle-operations@lists.ucla.edu';
$CFG->block_iclicker_notify_emails = 'ccle-operations@lists.ucla.edu';  // due to bad coding, two variables exist to do the same thing

// Site administration > Plugins > Licences > Manage licences
$CFG->sitedefaultlicense = 'tbd';

// Site administration > Plugins > Filters > MathJax
$CFG->forced_plugin_settings['filter_mathjaxloader']['texfiltercompatibility'] = 1;
$CFG->forced_plugin_settings['filter_mathjaxloader']['mathjaxconfig'] = '
    MathJax.Hub.Config({
        tex2jax: {
            inlineMath: [[\'$\',\'$\'], [\'\\\(\',\'\\\)\']],
            processEscapes: true
        },
        config: ["MMLorHTML.js", "Safe.js"],
        jax: ["input/TeX","input/MathML","output/HTML-CSS","output/NativeMML"],
        extensions: ["tex2jax.js","mml2jax.js","MathMenu.js","MathZoom.js"],
        TeX: {
            extensions: ["mhchem.js","AMSmath.js","AMSsymbols.js","noErrors.js","noUndefined.js"]
        },
        menuSettings: {
            zoom: "Double-Click",
            mpContext: true,
            mpMouse: true
        },
        errorSettings: { message: ["!"] },
        skipStartupTypeset: true,
        messageStyle: "none"
    });';

// Site administration > Plugins > Filters > Tabs
$CFG->forced_plugin_settings['filter_tabs']['enablebootstrap'] = 2; // Bootstrap 4 tabs.

// Site administration > Plugins > Repositories > Common repository settings
$CFG->legacyfilesinnewcourses = 1;  // enable new course to enable legacy course files

// Site administration > Plugins > Text editors > Atto HTML editor > Atto toolbar settings
$CFG->forced_plugin_settings['editor_atto']['toolbar'] = '
    collapse = collapse
    style1 = fontfamily, title, bold, italic, underline, backcolor, fontcolor, count
    list = unorderedlist, orderedlist
    indent = indent
    undo = undo
    links = link
    files = image, media, managefiles, kalturamedia, mediagallery
    other = htmlplus, fullscreen
    style2 = strike, subscript, superscript
    align = align, table, bsgrid
    insert = chemrender, chemistry, computing, equation, poodll, charmap, clear, pastespecial
    accessibility = accessibilitychecker, accessibilityhelper';

// CCLE-4849 - Number of groups displayed on first row of Atto HTML Editor
$CFG->forced_plugin_settings['atto_collapse']['showgroups'] = 8;

// Site administration > Plugins > Text editors > Atto HTML editor > Font family setting
$CFG->forced_plugin_settings['atto_fontfamily']['fontselectlist'] = '
    Default=Lato, Helvetica Neue, Helvetica, Arial, sans-serif;
    Arial=Arial, Helvetica, sans-serif;
    Times=Times New Roman, Times, serif;
    Courier=Courier New, Courier, mono;
    Georgia=Georgia, Times New Roman, Times, serif;
    Verdana=Verdana, Geneva, sans-serif;
    Trebuchet=Trebuchet MS, Helvetica, sans-serif;';

// Site administration > Plugins > Text editors > TinyMCE HTML editor > General settings
$CFG->forced_plugin_settings['editor_tinymce']['customtoolbar'] = '
    wrap,formatselect,wrap,bold,italic,wrap,bullist,numlist,wrap,link,unlink,wrap,image, mediagallery

    undo,redo,wrap,underline,strikethrough,sub,sup,wrap,justifyleft,justifycenter,justifyright,wrap,outdent,indent,wrap,forecolor,backcolor,wrap,ltr,rtl,

    nonbreaking, poodllaudiomp3, poodllvideo, poodllwhiteboard, poodllsnapshot, charmap,table

    fontselect,fontsizeselect,code,search,replace,wrap,cleanup,removeformat,pastetext,pasteword,wrap,fullscreen';

// Site administration > Plugins > Local plugins > UCLA configurations
$CFG->forced_plugin_settings['local_ucla']['registrar_cache_ttl'] = 3600;   // 1 hour

// Site administration > Security > Site policies
$CFG->forceloginforprofiles = true;
$CFG->forceloginforprofileimage = true; // temporary until "CCLE-2368 - PIX.PHP security fix" is done
$CFG->allowobjectembed = 1;
$CFG->maxeditingtime = 900; // 15 minutes
// Core Behat tests depend on default setting.
//$CFG->fullnamedisplay = 'lastname, firstname';
$CFG->cronclionly = true;

// Site administration > Security > HTTP security
$CFG->allowframembedding = 1; // CCLE-3021 - enabled because some collab sites need to be embedded

// Site administration > Appearance > Themes
//$CFG->theme = 'clean';

// Site administration > Appearance > Themes > Theme settings
$CFG->themedesignermode = 0;    // Need to be fast.
$CFG->allowcoursethemes = 1;
$CFG->custommenuitems = "helprequest,theme_uclashared|/blocks/ucla_help/index.php|helprequest
    selfhelp,theme_uclashared|https://docs.ccle.ucla.edu/|selfhelp
    tipsupdates,theme_uclashared|https://docs.ccle.ucla.edu/w/Tips_and_Updates|tipsupdates
    requestsite,theme_uclashared|/course/request.php|requestsite";

// Site administration > Appearance > Navigation
//$CFG->defaulthomepage = 1;    // user's home page should be "My Moodle" (HOMEPAGE_MY)
$CFG->navlinkcoursesections = 1; // CCLE-3031 - Section Titles breadcrumbs aren't links

// Site administration > Appearance > Courses
$CFG->courselistshortnames = 1;

// Site administration > Server > Session handling
$CFG->dbsessions = false;

// Site administration > Development > Experimental > Experimental settings
$CFG->dndallowtextandlinks = 1;

// Site administration > Development > Debugging
// Don't want core Behat tests to fail if debugging is enabled.
$CFG->debug = 0;    // NONE level debugging messages
$CFG->debugdisplay = 0;  // show the debugging messages

// Allowed Behat environment configs
// These are required to get Behat running in our very special environment.
//
// NOTE: These configs are active during Moodle @core tests, so we want to keep this list
//       as small as possible
$CFG->behat_extraallowedsettings = array(
    'forced_plugin_settings',               // Required for most UCLA based stuff to work
    'instructor_levels_roles',              // Required for 'office hours' block to work
    'allowcoursethemes',                    // Required for 'course edit' to work
    'forcedefaultmymoodle',                 // Experimental
    'debug',
    'debugdisplay',
    'customscripts',
    'custommenuitems'
);

// If you want to have un-revisioned configuration data, place in config_private
// $CFG->dirroot is overwritten later
$_dirroot_ = dirname(realpath(__FILE__)) . '/../../..';
$_config_private_ = $_dirroot_ . '/config_private.php';
if (file_exists($_config_private_)) {
    require_once($_config_private_);
}

// Site administration > Plugins > Enrolments > External database
// set external database connection settings after config_private.php has
// been read for the Registrar connection details
$CFG->forced_plugin_settings['enrol_database']['dbtype'] = $CFG->registrar_dbtype;
$CFG->forced_plugin_settings['enrol_database']['dbhost'] = $CFG->registrar_dbhost;
$CFG->forced_plugin_settings['enrol_database']['dbuser'] = $CFG->registrar_dbuser;
$CFG->forced_plugin_settings['enrol_database']['dbpass'] = $CFG->registrar_dbpass;
$CFG->forced_plugin_settings['enrol_database']['dbname'] = $CFG->registrar_dbname;
$CFG->forced_plugin_settings['enrol_database']['remoteenroltable'] = 'enroll2_test';
$CFG->forced_plugin_settings['enrol_database']['remotecoursefield'] = 'termsrs';
$CFG->forced_plugin_settings['enrol_database']['remoteuserfield'] = 'uid';
$CFG->forced_plugin_settings['enrol_database']['remoterolefield'] = 'role';
$CFG->forced_plugin_settings['enrol_database']['localcoursefield'] = 'id';
$CFG->forced_plugin_settings['enrol_database']['localrolefield'] = 'id';
$CFG->forced_plugin_settings['enrol_database']['unenrolaction'] = 2;    // ENROL_EXT_REMOVED_SUSPEND

// CCLE-2364 - SUPPORT CONSOLE (put after $_dirroot_, because needs $CFG->dataroot to be set)
$CFG->forced_plugin_settings['tool_uclasupportconsole']['log_apache_error'] = '/var/log/httpd/error_log';
$CFG->forced_plugin_settings['tool_uclasupportconsole']['log_apache_access'] = '/var/log/httpd/access_log';
$CFG->forced_plugin_settings['tool_uclasupportconsole']['log_apache_ssl_access'] = '/var/log/httpd/ssl_access_log';
$CFG->forced_plugin_settings['tool_uclasupportconsole']['log_apache_ssl_error'] = '/var/log/httpd/ssl_error_log';
$CFG->forced_plugin_settings['tool_uclasupportconsole']['log_apache_ssl_request'] = '/var/log/httpd/ssl_request_log';
$CFG->forced_plugin_settings['tool_uclasupportconsole']['log_course_creator'] = $CFG->dataroot . '/course_creator/';

// Enable customscripts for Respondus and other UCLA custom changes.
// NOTE: config is in local/ucla/configs, even though it is linked in root.
$CFG->customscripts = $_dirroot_ . "/local/ucla/customscripts";

// This will bootstrap the moodle functions.
require_once($_dirroot_ . '/lib/setup.php');

// EOF
