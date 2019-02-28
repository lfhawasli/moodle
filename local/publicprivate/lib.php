<?php
// This file is part of the UCLA public/private plugin for Moodle - http://moodle.org/
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
 * Library of interface functions and constants for public/private
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the public/private specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    local_publicprivate
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Notify nonenrolled users that they are viewing a public display of the
 * course. If they are not logged in, will display a login button.
 *
 * @global object $CFG
 * @global object $OUTPUT
 * @param object $course
 * @return string           Returns notice if any is needed.
 */
function notice_nonenrolled_users($course) {
    global $CFG, $OUTPUT;

    $context = context_course::instance($course->id);
    // if user is not enrolled in the course, then will need to display a notice
    if (is_enrolled($context) || has_capability('moodle/site:accessallgroups', $context)) {
        return; 
    }

    require_once($CFG->dirroot . '/local/publicprivate/lib/course.class.php');
    $publicprivate_course = new PublicPrivate_Course($course);
    if ($publicprivate_course->is_activated()) {
        $display_string = '';
        // if user is not logged in, then give them a login button
        if (isguestuser()) {
            $display_string = get_string('publicprivatenotice_notloggedin','local_publicprivate');
            $loginbutton = new single_button(new moodle_url('/login/index.php'),
                    get_string('publicprivatelogin','local_publicprivate'));
            $loginbutton->class = 'continuebutton';
            $display_string .= $OUTPUT->render($loginbutton);
        } else {
            $display_string = get_string('publicprivatenotice_notenrolled','local_publicprivate');
        }
        return $OUTPUT->box($display_string, 'alert alert-warning alert-login');
    }

    return;
}

/**
 * If the course for $mod->course has public/private enabled, then display
 * an editing button to enable/disable public/private.
 * 
 * @author ebollens
 * @version 20110719
 */
function get_private_public($mod, $sr = null) {
    global $CFG;
    require_once($CFG->dirroot.'/local/publicprivate/lib/course.class.php');
    $ppcourse = new PublicPrivate_Course($mod->course);
    $actions = array();

    // If public/private is not enabled, we cannot return anything.
    if (!$ppcourse->is_activated()) {
        return $actions;
    }

    $baseurl = new moodle_url('/local/publicprivate/mod.php', array('sesskey' => sesskey()));

    if ($sr !== null) {
        $baseurl->param('sr', $sr);
    }

    $public     = get_string("publicprivatemakepublic", "local_publicprivate");
    $private    = get_string("publicprivatemakeprivate", "local_publicprivate");

    if($ppcourse->is_activated()) {
        require_once($CFG->dirroot.'/local/publicprivate/lib/module.class.php');
        $ppmodule = new PublicPrivate_Module($mod->id);

        /**
         * If the module is private, show a toggle to make it public, or if it
         * is public, then show a toggle to make it private.
         */
        if($ppmodule->is_private()) {
            $actions[] = new action_menu_link_secondary(
                new moodle_url($baseurl, array('public' => $mod->id)),
                new pix_icon('t/locked', $public, 'moodle', array('class' => 'iconsmall')),
                $public,
                array('class' => 'editing_makepublic publicprivate')
            );
        } else {
            $actions[] = new action_menu_link_secondary(
                new moodle_url($baseurl, array('private' => $mod->id)),
                new pix_icon('t/lock', $private, 'moodle', array('class' => 'iconsmall')),
                $private,
                array('class' => 'editing_makeprivate publicprivate')
            );                    
        }
    }
    return $actions;
}