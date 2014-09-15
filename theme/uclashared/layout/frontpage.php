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
 * UCLA shared theme.
 *
 * Contains the general layout.
 *
 * @package    theme_uclashared
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Process and simplify all the options.
$hasfooter = (empty($PAGE->layout_options['nofooter']));
$hassidepre = (empty($PAGE->layout_options['noblocks']) && $PAGE->blocks->region_has_content('side-pre', $OUTPUT));
$hassidepost = (empty($PAGE->layout_options['noblocks']) && $PAGE->blocks->region_has_content('side-post', $OUTPUT));
$haslogininfo = (empty($PAGE->layout_options['nologininfo']));

$showsidepre = ($hassidepre && !$PAGE->blocks->region_completely_docked('side-pre', $OUTPUT));
$showsidepost = ($hassidepost && !$PAGE->blocks->region_completely_docked('side-post', $OUTPUT));

$bodyclasses = array();
if ($showsidepre && !$showsidepost) {
    $bodyclasses[] = 'side-pre-only';
} else if ($showsidepost && !$showsidepre) {
    $bodyclasses[] = 'side-post-only';
} else if (!$showsidepost && !$showsidepre) {
    $bodyclasses[] = 'content-only';
}

// Server environment flag.
$envflag = $OUTPUT->get_environment();

// Start HTML output.
echo $OUTPUT->doctype() ?>
<html <?php echo $OUTPUT->htmlattributes() ?>>
<head>
    <title><?php echo $PAGE->title ?></title>
    <?php echo $OUTPUT->standard_head_html() ?>
</head>
<body id="<?php echo $PAGE->bodyid ?>" class="<?php echo $PAGE->bodyclasses.' '.join(' ', $bodyclasses) ?>">
<?php echo $OUTPUT->standard_top_of_body_html() ?>
<div id="page" class="env-<?php echo $envflag ?>">

    <header id="page-header" class="container-fluid">
        <div class="header-main row">
            <div class="col-sm-1 col-xs-12">
                <div class="header-logo" >
                    <?php echo $OUTPUT->logo('ucla-logo', 'theme') ?>
                </div>
            </div>
            <div class="col-sm-11 col-xs-12 header-login">
                <div class="header-btn-group logininfo" >
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
//                    echo $OUTPUT->sublogo();
                ?>
                </div>
            </div>
            <div class="col-sm-10">
                <?php echo $OUTPUT->weeks_display() ?>
            </div>
        </div>
    </header>
<!-- END OF HEADER -->

    <div id="page-content">
        <?php echo $OUTPUT->alert_banner() ?>
        <div id="region-main-box">
            <div id="region-post-box">
                <div id="region-main-wrap" >
                    <div id="region-main">
                        <div class="region-content">
                            <?php echo $OUTPUT->blocks_for_region('side-pre') ?>
                            <?php echo core_renderer::MAIN_CONTENT_TOKEN ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($hassidepre) { ?>
                <div id="region-pre" class="block-region">
                    <div class="region-content">
                        <!--  empty on purpose-->
                    </div>
                </div>
                <?php } ?>
                
                <?php if ($hassidepost) { ?>
                <div id="region-post" class="block-region">
                    <div class="region-content">
                        <?php echo $OUTPUT->blocks_for_region('side-post') ?>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<?php
    // Include shared footer.
    require_once(__DIR__ . '/includes/footer.php');
?>
<?php echo $OUTPUT->standard_end_of_body_html() ?>
</body>
</html>
