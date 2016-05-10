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

/**
 * Called before theme outputs anything.
 * Inject a FERPA waiver for tools that send student data to a 3rd party.
 *
 * @param moodle_page $page
 */
function theme_uclashared_page_init(moodle_page $page) {
    global $USER;
    $context = $page->context;
    $url = $page->url;
    
    // Need to check for redirect layout or else we will end up in an infinite
    // loop, since the redirect() function calls page init again.
    if ($page->pagelayout != 'redirect' && isset($context) && isset($url)) {
        // If true, then user needs to sign waiver.
        if (local_ucla_ferpa_waiver::check($context, $url, $USER->id)) {
            $redirecturl = local_ucla_ferpa_waiver::get_link($context, $url);
            redirect($redirecturl, get_string('ferpawaiverrequired', 'local_ucla'), 0);
        }

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

/**
 * Process a CSS directive to load a font. Currently works for Bootstrap3 glyphicons.
 * SSC-2778: This filter now also gives choice for the frontpage image! The image
 * is specified in the config file, where its title is set in the config variable
 * "$CFG->forced_plugin_settings['theme_uclashared']['frontpage_image'];".
 *
 * @global type $CFG
 * @param type $css
 * @param type $theme
 * @return type
 */
function uclashared_process_css($css, $theme) {
    
    $tag = 'frontpage-image';
    $replacement = get_config('theme_uclashared', 'frontpage_image');
    $css = str_replace($tag, $replacement, $css);
    return $css;
}
