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
 * Link checker for UCLA Subject Links
 *
 * This script extracts links from files in the content directory
 * and checks them for broken links;
 *
 * @package    block_ucla_subject_links
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Get all html files in given directory and parse them for html
 */
// Script should only be run via CLI.
define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
require_once($CFG->dirroot. '/blocks/ucla_subject_links/block_ucla_subject_links.php');
require_once($CFG->dirroot.'/blocks/ucla_subject_links/classes/rollingcurlx.class.php');

// Script variables.
$showdebug = false;
$brokenlinks = array();    // Should be indexed by file and then link.

$pathtocheck = block_ucla_subject_links::get_location();
$directory = new RecursiveDirectoryIterator($pathtocheck);

$iterator = new RecursiveIteratorIterator($directory);
$regex = new RegexIterator($iterator, '/^.+\.htm$/i', RecursiveRegexIterator::GET_MATCH);

$numparallelreqs = 30;
$maxtimewait = 30; // Maximum amount of time (in seconds) to wait for a site to respond.

// Note, need to fake browser user agent, because some websites, like
// (Humanities!) are setup to prevent bots from accessing their websites.
// Got these headers by pinging an echo server with Chrome on a Linux machine.
$headers = array('Connection: keep-alive', // Mimic a Chrome browser.
                 'Upgrade-Insecure-Requests: 1',
                 // @codingStandardsIgnoreStart
                 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36',
                 // @codingStandardsIgnoreEnd
                 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                 'Accept-Encoding: gzip, deflate, sdch, br',
                 'Accept-Language: en-US,en;q=0.8');
$options = array(CURLOPT_FOLLOWLOCATION => true,   // Handle redirections.
                 CURLOPT_MAXREDIRS => 5,
                 CURLOPT_RETURNTRANSFER => true,   // Silence output.
                 CURLOPT_SSL_VERIFYPEER => false); // Don't veriy SSL Cert.

// Using third-party curl_multi wrapper. Source: https://github.com/marcushat/RollingCurlX.
$rcx = new RollingCurlX($numparallelreqs);
$rcx->setHeaders($headers);
$rcx->setOptions($options);
$rcx->setCallback('on_request_done');
$rcx->setTimeOut($maxtimewait * 1000);

// Files will be an array of paths to the html file we want to parse.
foreach ($regex as $files) {
    foreach ($files as $file) {
        cmd_debug('Working on ' . $file);
        $links = checkpage(file_get_contents($file));

        if (empty($links)) {
            cmd_debug('No links found');
            continue;   // No links!
        }

        foreach ($links as $link => $linktext) {
            // Found links, now see if they are alive.
            cmd_debug('Found link ' . $link);
            $customparams = array($file, $linktext);
            $rcx->addRequest($link, null, null, $customparams, null, null);  // NULL means defaults.
        }
    }
}

$rcx->execute();

// Now display results.
if (!empty($brokenlinks)) {
    echo "\nBroken links found:\n";
    foreach ($brokenlinks as $file => $links) {
        echo "File: $file\n";
        foreach ($links as $link => $text) {
            echo "  * $text ($link)\n";
        }
    }
} else {
    echo "No broken links found\n";
}

echo "DONE!\n";


// Script functions.

/**
 * Given page content, will parse it to find html links. Finds all anchor tags
 * with desired href. Ignores those appearing in comments.
 *
 * @link http://www.phptoys.com/tutorial/create-link-checker.html original source
 * @link http://htmlparsing.com/php.html new implementation
 *
 * @param string $content
 * @return array            Returns an array of links, in following format:
 *                          [link] => [link text]
 */
function checkpage($content) {
    $links = array();
    $dom = new DOMDocument;
    libxml_use_internal_errors(true); // Supress warnings about malformed html.
    $dom->loadHTML($content);
    $tags = $dom->getElementsByTagName('a');

    foreach ($tags as $tag) {
        $href = $tag->getAttribute('href');
        if ((strpos($href, 'http://') !== false) || strpos($href, 'https://') !== false) {
            $links[$href] = $tag->textContent;
        }
    }

    return $links;
}

/**
 * Throw away function because Moodle debugging() function is very cluttered
 * when reading it from the command line.
 *
 * @param string $message  The message to be conditionally shown.
 */
function cmd_debug($message) {
    global $showdebug;
    if ($showdebug) {
        echo $message . "\n";
    }
}

/**
 * Callback function for RollingCurlX requests. Checks to see if URL exists, then outputs
 * indicator to console and populates $brokenlinks for final output.
 * @codingStandardsIgnoreStart
 * @link http://stackoverflow.com/questions/981954/how-can-one-check-to-see-if-a-remote-file-exists-using-php/982045#982045 broken links
 * @codingStandardsIgnoreEnd
 * @link https://github.com/marcushat/RollingCurlX/blob/master/README.md function API
 *
 * @param string $response        HTTP body
 * @param string $link            URL to check
 * @param array  $info            Array returned by curl_getinfo($ch) plus error code, etc.
 * @param array  $customparams    Array of user data, currently ($file, $linktext)
 * @param float  $time            How long the request took
 * @return void
 */
function on_request_done($response, $link, $info, $customparams, $time) {
    global $brokenlinks;
    $file = $customparams[0];
    $linktext = $customparams[1];

    // First make sure that curl executed sucessfully.
    $curlretcode = $info['curle'];
    if ($curlretcode != CURLE_OK) {
        cmd_debug('cURL error code: ' . $curlretcode . " for link: " . $link);
    }

    $retcode = $info["http_code"];

    // If $retcode >= 400 -> not found, $retcode = 200, found.
    if ($retcode >= 400) {
        cmd_debug('HTTP error code: ' . $retcode);
        cmd_debug(sprintf("DEAD link found (%s) in %s\n", $link, $file));
        $brokenlinks[$file][$link] = $linktext;
        $foundbrokenlink = true;
    } else {
        cmd_debug('Link works! ' . $link);
        $foundbrokenlink = false;
    }

    // If $showdebug is false, show some kind of progress.
    // Indicator so we know script is processing.
    if (empty($showdebug)) {
        if (!empty($foundbrokenlink)) {
            echo '!';
        } else {
            echo '.';
        }
    }
}
