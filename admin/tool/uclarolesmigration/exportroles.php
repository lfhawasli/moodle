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
 * Export roles page.
 *
 * @package    tool_uclarolesmigration
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once(dirname(__FILE__).'/exportroles_form.php');

// Site context.
$context = context_system::instance();

// Require user to be logged in with permission to manage roles.
require_login();
require_capability('moodle/role:manage', $context);
admin_externalpage_setup('exportroles');

// Init the form object.
$form = new export_roles_form(null, array('contextid' => $context->id));

// Process the form if it has been validated.
if ($form->is_validated()) {
    $data = $form->get_data();
    $filenames = array();
    // Prepare our zip file.
    $zip = new ZipArchive();
    $zipname = 'exportzip.zip';
    // Use the designated temporary directory for temporary files.
    $tempzip = tempnam($CFG->tempdir . '/', $zipname);
    if ($zip->open($tempzip, ZipArchive::CREATE) !== true) {
        exit("cannot open <$tempzip>\n");
    }

    // Go through each role and make the XML file for each to add in the zip file.
    foreach ($data->export as $role) {
        // Grab role from DB.
        if ($role = $DB->get_record('role', array('shortname' => $role))) {
            $filename = $role->shortname . '.xml';
            // Use the designated temporary directory for temporary files.
            $tempfile = tempnam($CFG->tempdir . '/', $filename);
            file_put_contents($tempfile, core_role_preset::get_export_xml($role->id));
            $zip->addFile($tempfile, $role->shortname . '.xml');
            $tempfiles[] = $tempfile;
        }
    }
    $zip->close();

    // Check headers.
    if (headers_sent()) {
        echo 'HTTP header already sent';
    } else {
        $zipcontent = file_get_contents($tempzip);
        // Clean up temporary files.
        unlink($tempzip);
        foreach ($tempfiles as $tempfile) {
            unlink($tempfile);
        }
        // Prompt user for export zip download.
        send_file($zipcontent, 'exportzip.zip', 0, false, true, true);
    }

} else if ($form->is_submitted()) {
    $errormsg = get_string('error_noselect', 'tool_uclarolesmigration');
}

// Print the page header.
echo $OUTPUT->header();

// Print the page heading.
echo $OUTPUT->heading(get_string('selectrolestoexport', 'tool_uclarolesmigration'));
echo $OUTPUT->container_start();

// Print the error message if one is present.
if (isset($errormsg)) {
    echo $OUTPUT->notification($errormsg);
}

// Print the form.
$form->display();

// Print the end of the page.
echo $OUTPUT->container_end();
echo $OUTPUT->footer();
