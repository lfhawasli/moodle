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
?>
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

    <script type="text/javascript">
        // Loads headroom.js for auto-hiding header: http://wicky.nillia.ms/headroom.js/
        var header = document.querySelector(".header-main");

        // You can add some tolerance to the amount a user must scroll before header animates.
        // scroll-up: 5px
        // scroll down: immediate
        var headroom  = new Headroom(header, {
            tolerance : {
                up : 5,
                down : 0
            }
        });

        // initialise
        headroom.init();

    </script>
<?php }
