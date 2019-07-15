<?php
// This file is part of the UCLA Library Reserves block for Moodle - http://moodle.org/
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
 * Research Guide.
 *
 * @package    block_ucla_library_reserves
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot.'/blocks/ucla_library_reserves/locallib.php');

$course = get_course(required_param('courseid', PARAM_INT));
$url = new moodle_url('/blocks/ucla_library_reserves/index.php', array('courseid' => $course->id));
$context = context_course::instance($course->id, MUST_EXIST);
require_login($course);

init_pagex($course, $context, $url, BLOCK_UCLA_LIBRARY_RESERVES_LIB_GUIDE);

echo $OUTPUT->header();

print_library_tabs(get_string('researchguide', 'block_ucla_library_reserves'), $course->id);
$PAGE->set_pagelayout('incourse');

echo '<iframe id="contentframe" height="600px" width="100%" src="launch.php?id='.$course->id.'"webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';

    // Output script to make the iframe tag be as large as possible.
    $resize = '
        <script type="text/javascript">
        //<![CDATA[
            YUI().use("node", "event", function(Y) {
                var doc = Y.one("body");
                var frame = Y.one("#contentframe");
                var padding = 15; //The bottom of the iframe wasn\'t visible on some themes. Probably because of border widths, etc.
                var lastHeight;
                var resize = function(e) {
                    var viewportHeight = doc.get("winHeight");
                    if(lastHeight !== Math.min(doc.get("docHeight"), viewportHeight)){
                        frame.setStyle("height", viewportHeight - frame.getY() - padding + "px");
                        lastHeight = Math.min(doc.get("docHeight"), doc.get("winHeight"));
                    }
                };

                resize();

                Y.on("windowresize", resize);
            });
        //]]
        </script>
';
echo $resize;
echo $OUTPUT->footer();
