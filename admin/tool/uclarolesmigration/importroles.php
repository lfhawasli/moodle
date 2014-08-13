<?php
// This file is part of the UCLA roles migration plugin for Moodle - http://moodle.org/
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
 * Import roles main page.
 *
 * @package    tool_uclarolesmigration
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once(dirname(__FILE__).'/importroles_form.php');
require_once('lib.php');

// Site context.
$context = context_system::instance();

// Parameters from import configuration.
$createshortname = optional_param_array('to_create_shortname', array(), PARAM_RAW);
$createname = optional_param_array('to_create_name', array(), PARAM_RAW);
$rolestoreplace = optional_param_array('to_replace', array(), PARAM_RAW);
$roles = array('createshortname' => $createshortname, 'createname' => $createname, 'replace' => $rolestoreplace);
$actions = optional_param_array('actions', array(), PARAM_RAW);
$importxmls = optional_param_array('importxmls', array(), PARAM_RAW);

// Require user to be logged in with permission to manage roles.
require_login();
require_capability('moodle/role:manage', $context);
require_capability('moodle/restore:uploadfile', $context);
admin_externalpage_setup('importroles');

// Print the page header.
echo $OUTPUT->header();

// Print the page heading.
echo $OUTPUT->heading(get_string('importroles', 'tool_uclarolesmigration'));
echo $OUTPUT->container_start();

// Print the error message if one is present.
if (isset($errormsg)) {
    echo $OUTPUT->notification($errormsg, 'notifyproblem');
}


// Print the form.
$mform = new import_roles_form(null, array('roles' => $roles, 'actions' => $actions, 'importxmls' => $importxmls));
if ($mform->is_validated()) {
    require_once(dirname(__FILE__).'/do_import.php');
    $r = $CFG->wwwroot . '/' . $CFG->admin . '/roles/manage.php';
    echo html_writer::tag('p', get_string('link_to_define_roles', 'tool_uclarolesmigration', $r));
} else {
    $mform->display();
}

// Print the end of the page.
echo $OUTPUT->container_end();
echo $OUTPUT->footer();
