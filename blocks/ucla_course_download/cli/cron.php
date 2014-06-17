<?php
// This file is part of the UCLA course download plugin for Moodle - http://moodle.org/
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
 * Version file.
 *
 * @package     block_ucla_course_download
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');

// Required Moodle libraries.
require_once("$CFG->libdir/clilib.php");
require_once("$CFG->dirroot/blocks/moodleblock.class.php");

// Required UCLA libraries.
require_once("$CFG->dirroot/local/ucla/lib.php");
require_once("$CFG->dirroot/blocks/ucla_course_download/block_ucla_course_download.php");

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array('help' => false),
        array('h' => 'help'));

// Allow someone to choose specific term, courseid.
$courseid = $term = null;
if ($unrecognized) {
    foreach ($unrecognized as $index => $param) {
        // Maybe passing a term to run.
        if (ucla_validator('term', $param)) {
            $term = $param;
            unset($unrecognized[$index]);
        } else if (is_int($param)){
            // Maybe passing a courseid.
            $courseid = $param;
            unset($unrecognized[$index]);
        }
    }

    if (!empty($unrecognized)) {
        $unrecognized = implode("\n  ", $unrecognized);
        cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
    }
}

if ($options['help']) {
    $help =
"Process requests for the UCLA course download block.

If no term or courseid is specified, will process all the requests.

Options:
--currentterm         Run for the term specified in \$CFG->currentterm
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php blocks/ucla_course_download/cli/cron.php [TERM]
\$sudo -u www-data /usr/bin/php blocks/ucla_course_download/cli/cron.php [COURSEID]
";

    echo $help;
    die;
}

$trace = new text_progress_trace();
$blockcoursedownload = new block_ucla_course_download();
$blockcoursedownload->cron($trace);
$trace->output('DONE!');