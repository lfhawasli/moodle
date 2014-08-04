<?php

// Process and simplify all the options
$hasheading = ($PAGE->heading);
$hasnavbar = (empty($PAGE->layout_options['nonavbar']) 
    && $PAGE->has_navbar());
$hasfooter = (empty($PAGE->layout_options['nofooter']));
$hassidepre = (empty($PAGE->layout_options['noblocks']) 
    && $PAGE->blocks->region_has_content('side-pre', $OUTPUT));
$hassidepost = (empty($PAGE->layout_options['noblocks']) 
    && $PAGE->blocks->region_has_content('side-post', $OUTPUT));
$haslogininfo = (empty($PAGE->layout_options['nologininfo']));

// START UCLA MODIFICATION CCLE-2452
// Hide Control Panel button if user is not logged in
$showcontrolpanel = (!empty($PAGE->layout_options['controlpanel']) && isloggedin() && !isguestuser());

$showsidepre = ($hassidepre 
    && !$PAGE->blocks->region_completely_docked('side-pre', $OUTPUT));
$showsidepost = ($hassidepost 
    && !$PAGE->blocks->region_completely_docked('side-post', $OUTPUT));
    
//$PAGE->requires->yui_module('yui2-animation');

$custommenu = $OUTPUT->custom_menu();
$hascustommenu = (empty($PAGE->layout_options['nocustommenu']) 
    && !empty($custommenu));

$bodyclasses = array();
if ($showsidepre && !$showsidepost) {
    $bodyclasses[] = 'side-pre-only';
} else if ($showsidepost && !$showsidepre) {
    $bodyclasses[] = 'side-post-only';
} else if (!$showsidepost && !$showsidepre) {
    $bodyclasses[] = 'content-only';
}


if ($hascustommenu) {
    $bodyclasses[] = 'has_custom_menu';
}

$envflag = $OUTPUT->get_environment();


// Detect OS via user agent
$agent = $_SERVER['HTTP_USER_AGENT'];
$windowsos = strpos($agent, 'Windows') ? true : false;

// Do all drawing

echo $OUTPUT->doctype() ?>
<html <?php echo $OUTPUT->htmlattributes() ?>>
<head>
    <title><?php echo $PAGE->title ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->pix_url('favicon', 'theme')?>" />
    <link rel="apple-touch-icon" href="<?php echo $OUTPUT->pix_url('apple-touch-icon', 'theme')?>" />
    <?php 
    // Do not load font on Windows OS
    // Chrome and Firefox don't have proper font-smoothing
    // IE does have font-smoothing, so load font for IE 8 and above
    if(!$windowsos) { ?>
        <link href='https://fonts.googleapis.com/css?family=Lato:300,400,400italic,700,900' rel='stylesheet' type='text/css'>
        <!--<link href='http://fonts.googleapis.com/css?family=Open+Sans:400,300,400italic,600,700' rel='stylesheet' type='text/css'>-->
    <?php } ?>
    <?php echo $OUTPUT->standard_head_html() ?>
    <!--[if lte IE 8]>
        <script type="text/javascript" src="<?php echo $CFG->wwwroot . '/theme/uclashared/javascript/' ?>unsupported-browser.js"></script>
    <![endif]--> 
    <!--[if gt IE 8]>
        <link href='https://fonts.googleapis.com/css?family=Lato:400,400italic,700,900' rel='stylesheet' type='text/css'>   
    <![endif]-->
    
</head>
<body id="<?php echo $PAGE->bodyid ?>" class="<?php echo $PAGE->bodyclasses.' '.join(' ', $bodyclasses) ?>">
<?php echo $OUTPUT->standard_top_of_body_html() ?>
<div id="page" class="env-<?php echo $envflag ?>">

<?php if ($hasheading || $hasnavbar) { ?>
    <header id="page-header" class="container-fluid">
        <div class="header-main row">
            <div class="col-sm-6 col-xs-3">
                <div class="header-logo" >
                    <?php echo $OUTPUT->logo('ucla-logo', 'theme') ?>
                </div>
            </div>
            <div class="col-sm-6 col-xs-9 header-login">
                <div class="header-btn-group" >
                <?php
                    if ($haslogininfo) {
                        echo $OUTPUT->login_info();
                    }
                ?>
                </div>
            </div>            
        </div>
        <div class="header-system row" >
            <div class="col-sm-2">
                <div class="header-shared-server-list">
                <?php 
                    echo $OUTPUT->sublogo();
                ?>
                </div>
            </div>
            <div class="col-sm-10">
                <?php echo $OUTPUT->weeks_display() ?>
            </div>
        </div>
        
    </header>
<?php } ?>
<!-- END OF HEADER -->

    <div id="page-content">
        <?php
            // Determine if we need to display banner
            // @todo: right now it only works for 'red' alerts
            if(!during_initial_install() && get_config('block_ucla_alert', 'alert_sitewide')) {

                // If config is set, then alert-block exists... 
                // There might be some pages that don't load the block however..
                if(!class_exists('ucla_alert_banner_site')) {
                    $file = $CFG->dirroot . '/blocks/ucla_alert/locallib.php';
                    require_once($file);
                }
                
                // Display banner
                $banner = new ucla_alert_banner(SITEID);
                echo $banner->render();
            }
        ?>
        <div id="region-main-box">
            <div id="region-post-box">
                <div id="region-main-wrap" >
                    <div id="region-main">
                        <div class="region-content">
                            
                            <?php echo core_renderer::MAIN_CONTENT_TOKEN ?>
                            <?php echo $OUTPUT->blocks_for_region('side-post') ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($hassidepre) { ?>
                <div id="region-pre" class="block-region">
                    <div class="region-content">
                        <?php echo $OUTPUT->blocks_for_region('side-pre') ?>
                    </div>
                </div>
                <?php } ?>
                
                <?php if ($hassidepost) { ?>
                <div id="region-post" class="block-region">
                    <div class="region-content">
                        
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

    <!-- START OF FOOTER -->
    <?php if ($hasfooter) { ?>
    <div id="page-footer" >
    <!--
        <p class="helplink"><?php echo page_doc_link(get_string('moodledocslink')) ?></p>
    -->
        <span id="copyright-info">
        <?php echo $OUTPUT->copyright_info() ?>
        </span>

        <span id="footer-links">
        <?php echo $OUTPUT->footer_links() ?>
        </span>

        <?php echo $OUTPUT->standard_footer_html() ?>
    </div>
    <?php } ?>

<?php echo $OUTPUT->standard_end_of_body_html() ?>
</body>
</html>
