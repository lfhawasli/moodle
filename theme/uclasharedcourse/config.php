<?php
/**
 * Configuration for UCLA's Shared Server theme.
 *
 * For full information about creating Moodle themes, see:
 *  http://docs.moodle.org/en/Development:Themes_2.0
 *
 * @copyright 2010 UC Regents
 */

$THEME->name = 'uclasharedcourse';
$tn = 'theme_' . $THEME->name;

$THEME->parents = array(
    'uclashared',
    'base',
);

$THEME->sheets = array(
    'uclasharedcourse',
    'responsive',
);
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

$THEME->layouts = array(
    // Main course page
    'course' => array(
        'file' => $tf_course,
        'regions' => $enabledregions,
        'defaultregion' => $defaultregion,
        'options' => array(
            'controlpanel' => true,
            'customlogo' => true
        )
    ),
);

$THEME->csspostprocess = 'uclashared_process_css';
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->enable_dock = true;
