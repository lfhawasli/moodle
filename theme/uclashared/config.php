<?php
// This file is part of the UCLA shared course theme for Moodle - http://moodle.org/
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
 * Configuration for UCLA's Shared Server theme.
 *
 * For full information about creating Moodle themes, see:
 *  http://docs.moodle.org/en/Development:Themes_2.0
 * @package    theme_uclashared
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2010 UC Regents
 */

defined('MOODLE_INTERNAL') || die();
$THEME->name = 'uclashared';
$tn = 'theme_' . $THEME->name;


// Parent themes.

$THEME->parents = array('base', 'bootstrapbase');

// Style sheets from our current theme.
$THEME->sheets = array(
    'base',
    'core',
    'general',
    'ucla',
    'moodle',
    'components',
);

// Style sheets from parent themes that we want to exclude.
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

// YUI CSS we want to include.  (we don't want any!).
$THEME->yuicssmodules = array();

$tfgeneral     = 'course.php';
$tfcourse      = 'course.php';
$tfembedded    = 'embedded.php';
$tffrontpage   = 'frontpage.php';
$tfreport      = 'report.php';

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


 // CCLE-2882 - Control panel missing for some course pages.
 // Going to use global $COURSE object and if it isn't the SITEID, then will
 // enable the control panel for certain layouts that can exist in or out of courses.


global $COURSE;
// For most cases, user will usually be on course.
if (SITEID == $COURSE->id) {
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
    // Most backwards compatible layout without the blocks.
    // - this is the layout used by default.
    'base' => array(
        'file' => $tfgeneral,
        'regions' => array(),
        'options' => array(
            'controlpanel' => $displaycontrolpanel
        )
    ),
    // Standard layout with blocks, this is recommended for most.
    // pages with general information.
    'standard' => array(
        'file' => $tfgeneral,
        'regions' => $enabledregions,
        'defaultregion' => $defaultregion,
        'options' => array(
            'controlpanel' => $displaycontrolpanel
        )
    ),
    // Main course page.
    'course' => array(
        'file' => $tfcourse,
        'regions' => $enabledregions,
        'defaultregion' => $defaultregion,
        'options' => array(
            'controlpanel' => $displaycontrolpanel
        )
    ),
    'coursecategory' => array(
        'file' => $tfgeneral,
        'regions' => $enabledregions,
        'defaultregion' => $defaultregion,
    ),
    // Part of course, typical for modules - default page layout if.
    // $cm specified in require_login().
    'incourse' => array(
        'file' => $tfcourse,
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
        'options' => array(
            'controlpanel' => $displaycontrolpanel
        )
    ),
    // The site home page.
    'frontpage' => array(
        'file' => $tffrontpage,
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => $defaultregion,
        'options' => array(
            'controlpanel' => false,
        ),
    ),
    // Server administration scripts.
    'admin' => array(
        'file' => $tfgeneral,
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
        'options' => array(
            'controlpanel' => $displaycontrolpanel
        )
    ),
    // My dashboard page.
    'mydashboard' => array(
        'file' => $tfgeneral,
        'regions' => $enabledregions,
        'defaultregion' => $defaultregion,
        'options' => array(),
    ),
    // My public page.
    'mypublic' => array(
        'file' => $tfgeneral,
        'regions' => $enabledregions,
        'defaultregion' => $defaultregion,
    ),
    'login' => array(
        'file' => $tfgeneral,
        'regions' => array(),
        'options' => array(),
    ),

    // Pages that appear in pop-up windows - no navigation.
    // no blocks, no header.
    'popup' => array(
        'file' => $tfgeneral,
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
        'file' => $tfgeneral,
        'regions' => array(),
        'options' => array(
            'nofooter' => true,
            'controlpanel' => $displaycontrolpanel
        ),
    ),
    // Embeded pages, like iframe/object embeded in moodleform.
    // - it needs as much space as possible.
    'embedded' => array(
        'file' => $tfembedded,
        'regions' => array(),
        'options' => array(
            'nofooter' => true,
            'nonavbar' => true,
            'nocustommenu' => true
        ),
    ),
    // Should display the content and basic headers only.
    'print' => array(
        'file' => $tfgeneral,
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
        'file' => $tfembedded,
        'regions' => array(),
        'options' => array(
            'nofooter' => true,
            'nonavbar' => true,
            'nocustommenu' => true
        ),
    ),
    // The pagelayout used for reports.
    'report' => array(
        'file' => $tfreport,
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
