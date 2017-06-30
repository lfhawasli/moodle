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

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../../blocks/ucla_help/locallib.php');

// Process and simplify all the options.
$hasheading = ($PAGE->heading);
$hasnavbar = (empty($PAGE->layout_options['nonavbar']) && $PAGE->has_navbar());
$hasfooter = (empty($PAGE->layout_options['nofooter']));
$hassidepre = (empty($PAGE->layout_options['noblocks']) && $PAGE->blocks->region_has_content('side-pre', $OUTPUT));
$hassidepost = (empty($PAGE->layout_options['noblocks']) && $PAGE->blocks->region_has_content('side-post', $OUTPUT));
$haslogininfo = (empty($PAGE->layout_options['nologininfo']));

// START UCLA MODIFICATION CCLE-2452
// Hide Control Panel button if user is not logged in.
$showcontrolpanel = (!empty($PAGE->layout_options['controlpanel']) && isloggedin() && !isguestuser());

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

$bodyclasses[] = 'theme-' . $OUTPUT->themename;

$envflag = $OUTPUT->get_environment();

// Start HTML output.
echo $OUTPUT->doctype() ?>
<html <?php echo $OUTPUT->htmlattributes() ?>>
<head>
    <title><?php echo $PAGE->title ?></title>
    <?php echo $OUTPUT->standard_head_html() ?>
</head>
<body id="<?php echo $PAGE->bodyid ?>" class="<?php echo $PAGE->bodyclasses.' '.join(' ', $bodyclasses) ?> 
env-<?php echo $envflag ?>">
<?php echo $OUTPUT->standard_top_of_body_html() ?>

<?php
    // Include shared header.
    require_once(__DIR__ . '/includes/header.php');
?>

<div class="main container-fluid" >
    <div id="region-main-box">
        <div class="sidebar-buttons">
            <button class="btn btn-primary btn-sm block-toggle">
                <?php echo get_string('administration') ?>
            </button>
        </div>
        <?php echo core_renderer::MAIN_CONTENT_TOKEN ?>        
    </div>

</div>

<?php
    // Render a blocks sidebar.
    echo ucla_sidebar::block_pre($OUTPUT->blocks_for_region('side-pre'));
?>

<?php
    // Include shared footer.
    require_once(__DIR__ . '/includes/footer.php');
?>

<?php echo $OUTPUT->standard_end_of_body_html() ?>

</body>
</html>
