<?php

require_once(__DIR__ . '/../../../local/ucla_help/locallib.php');

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
$hasintrobanner = (!empty($PAGE->layout_options['introbanner']));

// START UCLA MODIFICATION CCLE-2452
// Hide Control Panel button if user is not logged in
$showcontrolpanel = (!empty($PAGE->layout_options['controlpanel']) && isloggedin() && !isguestuser());

$showsidepre = ($hassidepre 
    && !$PAGE->blocks->region_completely_docked('side-pre', $OUTPUT));
$showsidepost = ($hassidepost 
    && !$PAGE->blocks->region_completely_docked('side-post', $OUTPUT));

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

if ($hasintrobanner) {
    $bodyclasses[] = 'front-page';
}

if ($hascustommenu) {
    $bodyclasses[] = 'has_custom_menu';
}

$envflag = $OUTPUT->get_environment();


// Detect OS via user agent
$agent = $_SERVER['HTTP_USER_AGENT'];
$windowsos = strpos($agent, 'Windows') ? true : false;

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
        <link href='https://fonts.googleapis.com/css?family=Lato:400,400italic,700,900' rel='stylesheet' type='text/css'>
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

<?php if ($hasheading || $hasnavbar) { ?>
<header id="page-header" class="env-<?php echo $envflag ?>">
    <div class="">
        <div class="col-sm-6 header-logo">
            <?php echo $OUTPUT->logo('ucla-logo', 'theme') ?>
        </div>
        <div class="col-sm-6 header-login">
            <div class="header-links" >
            <?php
                if ($haslogininfo) {
                    echo $OUTPUT->login_info();
                }
            ?>
            </div>
        </div>
        <div class="header-login-frontpage col-sm-6" >
            <?php echo $OUTPUT->help_feedback_link() ?>
            <a class="login" href="<?php echo get_login_url() ?>">Login</a>
        </div>
    </div>
    <div class="system-identification " >
        <?php echo $OUTPUT->sublogo(); ?>
        <?php echo $OUTPUT->weeks_display() ?>
    </div>
</header>
<?php } ?>

<?php if ($hasnavbar && !$hasintrobanner) { ?>
<div class="navbar clearfix">
    <div class="breadcrumb"><?php echo $OUTPUT->navbar(); ?></div>
    <div class="controls" >
        <div class="navbutton navbar-control"> <?php echo $PAGE->button; ?></div>
        <?php if ($showcontrolpanel) { ?>
            <div class="control-panel navbar-control">
                <?php echo $OUTPUT->control_panel_button() ?>
            </div>
        <?php } ?>
    </div>
</div>
<?php } ?>

<div class="main container-fluid" >
    <div id="region-main-box">
        <div class="sidebar-buttons">
            <button class="btn btn-primary btn-sm block-toggle">
                Settings
            </button>
        </div>
        <?php echo core_renderer::MAIN_CONTENT_TOKEN ?>        
    </div>

</div>

<?php
    // Render a blocks sidebar.
    echo ucla_sidebar::block_pre($OUTPUT->blocks_for_region('side-pre'));
?>

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
