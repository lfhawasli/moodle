<?php
/**
 * Configuration for UCLA's Shared Server theme.
 *
 * For full information about creating Moodle themes, see:
 *  http://docs.moodle.org/en/Development:Themes_2.0
 *
 * @copyright 2010 UC Regents
 */

$THEME->name = 'uclashared';
$tn = 'theme_' . $THEME->name;

/**
 * Parent themes.
 */
$THEME->parents = array('base', 'bootstrapbase');

/**
 * Style sheets from our current theme.
 */
$THEME->sheets = array(
    'base',
    'core',
    'general',
    'ucla',
    'moodle',
    'components',
);

/**
 * Style sheets from parent themes that we want to exclude .
 */
$THEME->parents_exclude_sheets = array(
    'base' => array(
        'blocks',
        'dock'
    ),
    'bootstrapbase' => array(
        'moodle',
        'editor'
    )
);

/**
 * YUI CSS we want to include.  (we don't want any!)
 */
$THEME->yuicssmodules = array();

$tf_general     = 'course.php';
$tf_course      = 'course.php';
$tf_embedded    = 'embedded.php';
$tf_frontpage   = 'frontpage.php';
$tf_report      = 'report.php';

$noconfigs = during_initial_install();

if ($noconfigs) {
    $disablepostblocks = true;
} else {
    $disablepostblocks = get_config($tn, 'disable_post_blocks');
}

$defaultregion = 'side-post';
$enabledregions = array('side-pre');

if ($disablepostblocks) {
    $defaultregion = 'side-pre';
} else {
    $enabledregions[] = 'side-post';
}

/**
 * CCLE-2882 - Control panel missing for some course pages
 * 
 * Going to use global $COURSE object and if it isn't the SITEID, then will
 * enable the control panel for certain layouts that can exist in or out of 
 * courses
 */
global $COURSE;
if (SITEID == $COURSE->id) {   // for most cases, user will usually be on course
    $displaycontrolpanel = false;
} else {
    // CCLE-4941 - If the course format is something other than UCLA, then remove the Control Panel button.
    include_once("$CFG->dirroot/course/format/lib.php");
    $format = course_get_format($COURSE);
    if ($format->get_format() == 'ucla') {
        $displaycontrolpanel = true;
    } else {
        $displaycontrolpanel = false;
    }
}

$THEME->layouts = array(
    // Most backwards compatible layout without the blocks 
    // - this is the layout used by default
    'base' => array(
        'file' => $tf_general,
        'regions' => array(),
        'options' => array(
            'controlpanel' => $displaycontrolpanel
        )
    ),
    // Standard layout with blocks, this is recommended for most 
    // pages with general information
    'standard' => array(
        'file' => $tf_general,
        'regions' => $enabledregions,
        'defaultregion' => $defaultregion,
        'options' => array(
            'controlpanel' => $displaycontrolpanel
        )
    ),
    // Main course page
    'course' => array(
        'file' => $tf_course,
        'regions' => $enabledregions,
        'defaultregion' => $defaultregion,
        'options' => array(
            'controlpanel' => $displaycontrolpanel
        )
    ),
    'coursecategory' => array(
        'file' => $tf_general,
        'regions' => $enabledregions,
        'defaultregion' => $defaultregion,
    ),
    // part of course, typical for modules - default page layout if 
    // $cm specified in require_login()
    'incourse' => array(
        'file' => $tf_course,
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
        'options' => array(
            'controlpanel' => $displaycontrolpanel
        )
    ),
    // The site home page.
    'frontpage' => array(
        'file' => $tf_frontpage,
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => $defaultregion,
        'options' => array(
            'controlpanel' => false,
        ),
    ),
    // Server administration scripts.
    'admin' => array(
        'file' => $tf_general,
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
        'options' => array(
            'controlpanel' => $displaycontrolpanel
        )
    ),
    // My dashboard page
    'mydashboard' => array(
        'file' => $tf_general,
        'regions' => $enabledregions,
        'defaultregion' => $defaultregion,
        'options' => array(),
    ),
    // My public page
    'mypublic' => array(
        'file' => $tf_general,
        'regions' => $enabledregions,
        'defaultregion' => $defaultregion,
    ),
    'login' => array(
        'file' => $tf_general,
        'regions' => array(),
        'options' => array(),
    ),

    // Pages that appear in pop-up windows - no navigation, 
    // no blocks, no header.
    'popup' => array(
        'file' => $tf_general,
        'regions' => array(),
        'options' => array( 
            'nofooter' => true, 
            'nonavbar' => true, 
            'nocustommenu' => true, 
            'nologininfo' => true
        ),
    ),
    // No blocks and minimal footer - used for legacy frame layouts only!
    'frametop' => array(
        'file' => $tf_general,
        'regions' => array(),
        'options' => array(
            'nofooter' => true,
            'controlpanel' => $displaycontrolpanel
        ),
    ),
    // Embeded pages, like iframe/object embeded in moodleform 
    // - it needs as much space as possible
    'embedded' => array(
        'file' => $tf_embedded,
        'regions' => array(),
        'options' => array(
            'nofooter' => true, 
            'nonavbar' => true, 
            'nocustommenu' => true
        ),
    ),
    // Should display the content and basic headers only.
    'print' => array(
        'file' => $tf_general,
        'regions' => array(),
        'options' => array(
            'noblocks' => true, 
            'nofooter' => true, 
            'nonavbar' => false, 
            'nocustommenu' => true,
            'nologininfo' => true
        ),
    ),
    // The pagelayout used when a redirection is occuring.
    'redirect' => array(
        'file' => $tf_embedded,
        'regions' => array(),
        'options' => array(
            'nofooter' => true, 
            'nonavbar' => true, 
            'nocustommenu' => true
        ),
    ),
    // The pagelayout used for reports
    'report' => array(
        'file' => $tf_report,
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
        'options' => array(
            'controlpanel' => $displaycontrolpanel
        )
    ),
);

$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->enable_dock = false;
$THEME->csspostprocess = 'uclashared_process_css';
$THEME->javascripts[] = 'shared_server_dropdown';
$THEME->javascripts[] = 'help_feedback';
$THEME->javascripts[] = 'headroom.min';

// CCLE-4807 - Atto Chemistry: Overriding plugin styles.
$THEME->plugins_exclude_sheets = array('atto' => array('chemistry'));
