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

?>

<?php if ($hasheading || $hasnavbar) { ?>
    <header id="page-header" class="container-fluid">
        <div class="header-main row">
            <div class="col-sm-6 col-xs-3 header-logo">
                <?php echo $OUTPUT->logo('ucla-logo', 'theme') ?>
            </div>
            <div class="col-sm-6 col-xs-9 header-login">
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
            <div class="col-sm-6">
                <?php echo $OUTPUT->sublogo(); ?>
            </div>
            <div class="col-sm-6">
                <?php echo $OUTPUT->weeks_display() ?>
            </div>
        </div>
    </header>
<?php
} 

if ($hasnavbar) { ?>
<div class="navbar container-fluid">
    <div class="row">
        <div class="navbar-breadcrumb col-sm-5 col-md-6">
            <?php echo $OUTPUT->navbar(); ?>
        </div>
        <div class="navbar-controls col-sm-7 col-md-6" >
            <div class="navbutton navbar-control">
                <?php echo $PAGE->button; ?>
            </div>
            <?php if ($showcontrolpanel) { ?>
            <div class="control-panel navbar-control">
                <?php echo $OUTPUT->control_panel_button() ?>
            </div>
            <?php } ?>
        </div>
    </div>
</div>
<?php
}

