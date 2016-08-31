<?php
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/mod/mediasite/basiclti_mediasite_lib.php');

function mediasite_extend_navigation_user_settings(navigation_node $parentnode, stdClass $user, context_user $context, stdClass $course, context_course $coursecontext) {
    //blowup('mediasite_extend_navigation_user_settings');
    // mediasite_navigation_extension();
    // this works . . . I think
}

function mediasite_navigation_extension_mymediasite() {
    global $PAGE;
    $mediasiteNode = $PAGE->navigation->add(get_string('mediasite', 'mediasite'), null, navigation_node::TYPE_COURSE);    
    $myMediasiteNode = $mediasiteNode->add(get_string('my_mediasite', 'mediasite'), new moodle_url('/mod/mediasite/mymediasite.php'));
    $myMediasiteNode->make_active();
    return $mediasiteNode;
}

function mediasite_extend_navigation_course_settings(navigation_node $parentnode, context_course $context) {
    $OVERRIDE_CAPABILITY = 'mod/mediasite:overridedefaults';
    if (!has_capability($OVERRIDE_CAPABILITY, $context)) {
        return;
    }
    global $PAGE;
    $label = get_string('course_settings', 'mediasite');
    $key = 'mediasite_course_settings';
    $courseSettings = $parentnode->get($key);
    if ($courseSettings == null && $PAGE->course->id > 1) {
        $courseSettings = $parentnode->add($label, new moodle_url('/mod/mediasite/site/course_settings.php', array('id' => $PAGE->course->id)), navigation_node::TYPE_SETTING, null, $key, new pix_icon('i/settings', $label));
        // $courseSettings->make_active();
    }
    return $courseSettings;
}

function mediasite_navigation_extension_mymediasite_placement() {
    if (!isLoggedIn()) {
        return;
    }

    global $PAGE, $USER, $DB;

    if (empty($USER->id)) {
        return;
    }

    $SITE_PAGES_ID = 1;
    $id = optional_param('id', $SITE_PAGES_ID, PARAM_INT);
    if (($PAGE->course != null)) {
        // BUG44083:    MOODLE: Naviagation error in Moodle if MyMediasite clicked while viewing media
        // debugging('using $PAGE->course->id ('.$PAGE->course->id.') instead of $id ('.$id.').');
        $id = $PAGE->course->id;
    }
    $MY_MEDIASITE_CAPABILITY = 'mod/mediasite:mymediasite';
    $myMediasitePlacements = get_mediasite_sites(false, true);
    // $sitePagesNode = $PAGE->navigation->find($SITE_PAGES_ID, navigation_node::TYPE_COURSE);
    $sitePagesNode = $PAGE->navigation->get('home');
    $courseNode = null;
    $courseContext = null;
    $courseMediasiteSite = null;
    $userContext = context_user::instance($USER->id);

    if ($PAGE->course != null && $PAGE->course->id != $SITE_PAGES_ID) {
        $courseContext = context_course::instance($PAGE->course->id);
        $courseNode = $PAGE->navigation->find($PAGE->course->id, navigation_node::TYPE_COURSE);
        $courseMediasiteSite = $DB->get_field('mediasite_course_config', 'mediasite_site', array('course' => $PAGE->course->id));
    }


    foreach ($myMediasitePlacements as $site) {
        $url = new moodle_url('/mod/mediasite/mymediasite.php', array('id' => $id, 'siteid'=>$site->id));
        switch ($site->my_mediasite_placement) {
            case mediasite_menu_placement::SITE_PAGES:
                // debugging('SITE_PAGES $site->id: '.$site->id.' : $site->my_mediasite_title: '.$site->my_mediasite_title.' : has_capability($MY_MEDIASITE_CAPABILITY, $userContext): '.has_capability($MY_MEDIASITE_CAPABILITY, $userContext));
                if (has_capability($MY_MEDIASITE_CAPABILITY, $userContext)) {
                    // debugging('do this');
                    $sitePagesNode->add($site->my_mediasite_title, $url);
                }
                break;
            case mediasite_menu_placement::COURSE_MENU:
                if ($courseContext != null && $courseNode != null && $courseMediasiteSite != null && $courseMediasiteSite == $site->id && has_capability($MY_MEDIASITE_CAPABILITY, $courseContext)) {
                    $courseNode->add($site->my_mediasite_title, $url);
                }
                break;
            default:
                debugging('The value for my_mediasite_placement in mediasite_navigation_extension_mymediasite_placement is not valid. The value was '.$site->get_my_mediasite_placement().'.');
        }
    }
}

function mediasite_navigation_extension_courses7_course() {
    
    global $PAGE, $DB;
    $course = $PAGE->course;
    if ($course && $course->id > 1) {
        $context = context_course::instance($course->id);
        if (!has_capability('mod/mediasite:courses7', $context)) {
            //blowup('user does not have mediasite:courses7 capability');
            return;
        }
        $courseConfig = $DB->get_record('mediasite_course_config', array('course' => $course->id), '*', IGNORE_MISSING);

        $courseNode = $PAGE->navigation->find($course->id, navigation_node::TYPE_COURSE);

        // debugging('is_numeric:'.is_numeric($courseConfig->mediasite_courses_enabled).' $courseConfig->mediasite_courses_enabled: '.$courseConfig->mediasite_courses_enabled.' $courseConfig->mediasite_courses_enabled > 1: "'.($courseConfig->mediasite_courses_enabled > 1).'" is_bool($courseConfig->mediasite_courses_enabled > 1): '.is_bool($courseConfig->mediasite_courses_enabled > 1));

        if (!$courseConfig) {
            foreach (get_mediasite_sites(true, false) as $site) {
                $coursesNode = $courseNode->add($site->integration_catalog_title, new moodle_url('/mod/mediasite/courses7.php', array('id'=>$course->id, 'siteid'=>$site->id)));
            }
        } else if ($courseConfig->mediasite_courses_enabled) {
            $site = new Sonicfoundry\MediasiteSite($courseConfig->mediasite_site);
            $coursesNode = $courseNode->add($site->get_integration_catalog_title(), new moodle_url('/mod/mediasite/courses7.php', array('id'=>$course->id, 'siteid'=>$courseConfig->mediasite_site)));
        }
    }
}

function get_mediasite_sites($onlyIntegrationCatalogEnabled = false, $onlyMyMediasiteEnabled = false){
    global $DB;
    $select = '';
    $sort = '';
    if ($onlyIntegrationCatalogEnabled && $onlyMyMediasiteEnabled) {
        $select = 'show_integration_catalog > 1 AND show_my_mediasite = 1';
    }
    else if ($onlyIntegrationCatalogEnabled) {
        $select = 'show_integration_catalog > 1';
        $sort = 'integration_catalog_title';
    } else if ($onlyMyMediasiteEnabled) {
        $select = 'show_my_mediasite = 1';
        $sort = 'my_mediasite_title';
    }
    return $DB->get_records_select('mediasite_sites', $select, null, $sort, '*');
}

function blowup($msg) {
    //throw new moodle_exception('generalexceptionmessage', 'error', '', $msg);    
    debugging($msg);
}
?>