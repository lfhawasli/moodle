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
 * Pulls random image for homepage background and saves it to session cache.
 *
 * @return $image.
 */
function theme_uclashared_frontpageimage() {
    global $CFG;
    $frontpageimagecache = cache::make('theme_uclashared', 'frontpageimage');
    if (!($frontpageimagecache->get('image'))) {
        $imagedir = $CFG->dirroot . "/theme/uclashared/pix/frontpageimages";
        $files = glob($imagedir . '/*.*');
        $file = array_rand($files);
        $filename = basename($files[$file]);
        $image = $imagedir . "/" . $filename;
        $frontpageimagecache->set('image', $image);
    } else {
        $image = $frontpageimagecache->get('image');
    }
    return $image;
}

/**
 * Inject additional SCSS from scss directory.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_uclashared_get_extra_scss($theme) {
    global $CFG;
    $content = '';
    // Go through scss directory.
    $directory = $CFG->dirroot . '/theme/uclashared/scss/';
    // Ignore the . and .. results.
    $files = array_slice(scandir($directory), 2);

    foreach ($files as $file) {
        $content .= file_get_contents($directory . $file);
    }

    return $content;
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
}
