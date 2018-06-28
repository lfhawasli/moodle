<?php
// This file is part of the UCLA theme for Moodle - http://moodle.org/
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
 * Theme functions called by Moodle core code.
 *
 * @package     theme_uclashared
 * @copyright   2015 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Callback to add head elements.
 *
 * @return str valid html head content
 */
function theme_uclashared_before_standard_html_head() {
    // Add link to Google Font for Lato.
    return '<link href="https://fonts.googleapis.com/css?family=Lato:300,400,400i,700,900" rel="stylesheet" type="text/css">';
}

/**
 * Pulls random image for homepage background.
 *
 * @return array    Image URL and caption.
 */
function theme_uclashared_frontpageimage() {
    global $CFG, $OUTPUT;
    $retval = array();
    $imagedir = $CFG->dirroot . '/theme/uclashared/pix/frontpageimages';
    $files = glob($imagedir . '/*.*');
    $file = array_rand($files);
    $filename = basename($files[$file]);

    // Get caption.
    $retval['credits'] = explode(".", $filename)[0];

    // Get image url.
    $retval['image'] = $CFG->wwwroot . '/theme/uclashared/pix/frontpageimages/' . $filename;

    return $retval;
}

/**
 * Adds or overrides icon mapping for fontawesome icons.
 *
 * @return array
 */
function theme_uclashared_get_fontawesome_icon_map() {
     return [
         'core:e/styleprops' => 'fa-paragraph',
         'core:e/text_highlight_picker' => 'fa-tint',
         'core:e/text_highlight' => 'fa-tint',
     ];
}

/**
 * Called before theme outputs anything.
 *
 * @param moodle_page $page
 */
function theme_uclashared_page_init(moodle_page $page) {
    $context = $page->context;
    $url = $page->url;

    // Need to check for redirect layout or else we will end up in an infinite
    // loop, since the redirect() function calls page init again.
    if ($page->pagelayout != 'redirect' && isset($context) && isset($url)) {
        // Do not attempt to autologin on login pages.
        $urlstring = $url->out();
        if (strpos($urlstring, '/login/') !== false ||
                strpos($urlstring, '/auth/') !== false ||
                strpos($urlstring, '/local/mobile/') !== false) {
            return;
        }

        // Try to auto-login if not logged in.
        local_ucla_autologin::detect();
    }

    // Need to check for the context of the page to render the activity menu.
    // See CCLE-7736 for additional notes.
    if ($context->contextlevel == CONTEXT_MODULE) {
        $page->force_settings_menu();
    }
}
