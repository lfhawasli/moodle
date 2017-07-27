<?php
// This file is part of the UCLA local plugin for Moodle - http://moodle.org/
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
 * Run after every run of Moodle cron, to see if it produced any errors.
 *
 * No output unless errors found, and then that is emailed by the cron job to
 * the cron job owner. So check who your ~moodle/.forward points to.
 *
 * When script is done reading log file, it writes filesize to file in
 * moodledata dir, so that next run will start where this one left off.
 *
 * DEPLOYMENT: Add to moodle cron crontab entry, as in the example below where
 * it fits between cron.php and logrotate.
 *
 * 5 * * * * /usr/bin/php /data/prod/moodle/admin/cli/cron.php >> /data/prod/moodledata/cron.log &&
 *  /usr/bin/php /data/prod/moodle/local/ucla/cli/moodle_cron_errors.php &&
 *  /usr/sbin/logrotate /home/moodle/logrotate.prod.cron.conf -s /home/moodle/prod.cron.logrotate-status
 *
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');

// CONFIG SECTION.
$searchitems = array("error", "warning");  // Allowing for possible future search of other things.
$logfile = get_config('tool_uclasupportconsole', 'log_moodle_cron');
$logfilesize = $CFG->dataroot . "/moodle_cron_size.dat";
$debug = 0;

// Checks log filesize against previous days.
$filesize = filesize($logfile);
if (file_exists($logfilesize)) {
    $oldlogsize = file($logfilesize);
    $oldsize = rtrim($oldlogsize[0]);  // Remove any carriage returns.
} else {
    $oldsize = 0;
    if ($debug) {
        echo "$logfilesize didn't exist.\n";
    }
}
if ($debug) {
    echo "$logfile is $filesize bytes compared to old size of $oldsize.\n";
}

// Save log filesize to file.
$fh = fopen($logfilesize, 'w') or die("can't open file");
fwrite($fh, "$filesize\n");
fclose($fh);

if ($fp = fopen($logfile, 'r')) { // Make sure it opens the log file.
    if ($filesize > $oldsize) {  // If log fileseze greater than last run, start at last stopping point with fseek.
        fseek($fp, $oldsize); // Seek $oldsize bytes into file.
        if ($debug) {
            echo "Seeking in file $oldsize bytes\n";
        }
    } else if ($filesize < $oldsize) { // If filesize is smaller than old size, the log file has been rotated, start from 0.
        fseek($fp, 0);
        if ($debug) {
            echo "Seeking in file 0 bytes\n";
        }
    } else if ($filesize == $oldsize) { // Logfile hasn't grown since last check.
        if ($debug) {
            echo "$logfile hasn't changed size from $oldsize\n";
        }
        exit;
    }

    $matchedlines = array();
    $prevline = ""; // Used for storing line in previous loop iteration to prepend to a matched line.

    while ($line = fgets($fp)) {  // While it can get a line, loop through them looking for matches.
        foreach ($searchitems as $value) {
            if (preg_match("/$value/i", $line) and
                    !preg_match("/errors=0/", $line) and
                    !preg_match("/Sending post /", $line) and
                    !preg_match("/users were sent post /", $line)) {
                if (preg_match("/SimplePie/", $line)) {
                    // If we see errors related to SimplePie, it is because of
                    // a RSS error. We want to ignore RSS errors since they
                    // happen often and we cannot do anything about it. So,
                    // unset previous matched line.
                    array_pop($matchedlines);
                } else {
                    // Save this matched line as well as the line above it.
                    $matchedlines[] = $prevline . $line;
                }
            }
            $prevline = $line; // Store previous line.
        }
    }
    fclose($fp); // Close the log file.
    if (count($matchedlines) > 0) {
        echo $CFG->wwwroot . " moodle cron monitoring script moodle_cron_errors.php found the following in $logfile:\n";
        echo implode('', $matchedlines);
    }
}
