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
         'core:e/insert_edit_video' => 'fa fa-film',
         'atto_chemistry:icon' => 'fa-flask',
         'atto_computing:icon' => 'fa-desktop',
         'atto_bsgrid:ed/iconone' => 'fa-columns',
         'atto_poodll:audiomp3' => 'fa-microphone',
         'atto_poodll:video' => 'fa-video-camera',
         'atto_poodll:whiteboard' => 'fa-pencil',
         'atto_poodll:snapshot' => 'fa-camera',
         'atto_fontfamily:icon' => 'fa-font',
         'core:i/navigationitem' => 'fa-cog',
     ];
}

/**
 * Returns the main SCSS content.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_uclashared_get_main_scss_content($theme) {
    global $CFG;

    // Default from Boost.
    $scss = file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');

    // Pre CSS.
    $pre = file_get_contents($CFG->dirroot . '/theme/uclashared/scss/pre.scss');
    // Post CSS.
    $post = file_get_contents($CFG->dirroot . '/theme/uclashared/scss/post.scss');

    // Combine them together.
    return $pre . "\n" . $scss . "\n" . $post;
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
    // CCLE-7787 Hide activity menu on Quiz attempt and preview pages.
    if (($context->contextlevel == CONTEXT_MODULE) && !('mod-quiz-attempt' == $page->pagetype || 'mod-quiz-review' == $page->pagetype)) {
        $page->force_settings_menu();
    }
}

    /**
     * Calls weeks display function and returns an array with current week and quarter.
     *
     * @return array Current week and quarter strings separated into an array.
     */
    function theme_uclashared_parsed_weeks_display() {
        $parsed = array();
        $weeksdata = get_config('local_ucla', 'current_week_display');
        // Fix front page quarter and week display for summer.
        // Case when there is a session overlap for summer.
        if (stripos($weeksdata, 'summer') !== false) {
            $weeksdata = trim(strip_tags($weeksdata));
            if(strpos($weeksdata, '|') !== false) {
                list($session1, $session2) = explode('|', $weeksdata);
                list($quarter, $week1) = explode('-', $session1);
                $week1 = substr_replace($week1, ' - ', 10, 0);
                // Case when there is a session overlap for summer.
                if($session2) {
                    list($quarter, $week2) = explode('-', $session2);
                    $week2 = $week2 ? "<br/>" . substr_replace($week2, ' - ', 10, 0) : '';
                }
                $week = $week1 . $week2;
            } else {
                // Case when there is no session overlap for summer.
                $session = explode('|', $weeksdata);
                list($quarter, $week) = explode('-', $session[0]);
                $week = substr_replace($week, ' - ', 10, 0);
                $week = strip_tags($week);
            }
        } else {
            // Case for regular session.
            $weekpos = strpos($weeksdata, '<span class="week">');
            $quarter = substr($weeksdata, 0, $weekpos);
            $week = substr($weeksdata, $weekpos);
            $week = strip_tags($week);
        }
        $quarter = trim(strip_tags($quarter));
        $week = trim($week);
        array_push($parsed, $quarter, $week);
        return $parsed;
    }
