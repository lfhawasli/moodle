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
 * Import roles form.
 *
 * @package    tool_uclarolesmigration
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
/**
 * Import roles moodleform class.
 *
 * @package    tool_uclarolesmigration
 * @category   form
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_roles_form extends moodleform {
    /**
     * The form definition.
     * Calls on import_roles_upload_form to accept a zip file and processes
     * the zip file's xml files into an array of xml strings.  After processing,
     * the form accepts options from the user to either create, replace, or ignore
     * specific role imports by calling the import_config_table function.
     */
    protected function definition() {
        global $CFG;
        $mform =& $this->_form;
        $roles = $this->_customdata['roles'];
        $actions = $this->_customdata['actions'];
        $importxmls = $this->_customdata['importxmls'];
        $uploadform = new import_roles_upload_form();
        if ($uploadform->is_validated()) {
            $zipname = 'importzip.zip';
            $zippath = tempnam($CFG->tempdir . '/', $zipname);
            // Save the zip file as a temporary file.
            $uploadform->save_file('importfile', $zippath, true);
            $zip = zip_open($zippath);
            // Process every file within the zip file.
            while ($xmlfile = zip_read($zip)) {
                if (zip_entry_open($zip, $xmlfile, "r")) {
                    $xmlstring = zip_entry_read($xmlfile, zip_entry_filesize($xmlfile));
                    $shortname = basename(zip_entry_name($xmlfile), '.xml');
                    $importxmls[$shortname] = $xmlstring;
                    zip_entry_close($xmlfile);
                    $mform->addElement('hidden', 'importxmls[]', $xmlstring);
                }
            }
            zip_close($zip);
            // Clean up the zip file.
            unlink($zippath);
            $table = import_config_table($importxmls, $actions);
            $mform->addElement('html', html_writer::table($table));
            $mform->setType('importxmls', PARAM_RAW);
            $this->add_action_buttons(false, get_string('next'));
        } else if (empty($this->_customdata['actions'])) {
            $mform->addElement('html', $uploadform->display());
        }
    }

    /**
     * Validates the form, ensuring that at least
     * one action is defined.
     *
     * @param array $data submitted data, not used
     * @param array $files not used
     * @return array $errors
     */
    public function validation($data, $files) {
        $errors = array();
        if (empty($this->_customdata['actions'])) {
            $errors['roles'] = get_string('error_noaction', 'tool_uclarolesmigration');
        }
        return $errors;
    }
}

/**
 * Import roles upload moodleform class.
 *
 * Accepts a zip file to import roles.
 *
 * @package    tool_uclarolesmigration
 * @category   form
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_roles_upload_form extends moodleform {

    /**
     * The form definition.
     * Also has rules to ensure that a zip file is uploaded.
     */
    protected function definition() {
        $mform =& $this->_form;
        $mform->addElement('filepicker', 'importfile', get_string('files'), null, array('accepted_types' => 'zip'));
        $mform->addRule('importfile', get_string('error_nofile', 'tool_uclarolesmigration'), 'required');
        $this->add_action_buttons(false, get_string('next'));
    }

    /**
     * Validates the form, ensuring that the uploaded zip file is valid
     * and contains valid role XML files.
     *
     * @param array $data submitted data, not used
     * @param array $files not used
     * @return array $errors
     */
    public function validation($data, $files) {
        global $CFG, $OUTPUT;
        $errors = array();

        if ($file = $this->get_draft_files('importfile')) {
            // Set up and open temporary zip file.
            $file = reset($file);
            $content = $file->get_content();
            $zipname = 'importzip.zip';
            $zippath = tempnam($CFG->tempdir . '/', $zipname);
            // Save the zip file as a temporary file.
            file_put_contents($zippath, $content);
            $zip = zip_open($zippath);

            // Verify that the zip file was able to open.
            if (!is_resource($zip)) {
                $errors['importfile'] = get_string('error_invalidzip', 'tool_uclarolesmigration');
            } else {
                // Verify every XML file within the zip file.
                while ($xmlfile = zip_read($zip)) {
                    if (zip_entry_open($zip, $xmlfile, "r")) {
                        $xmlstring = zip_entry_read($xmlfile, zip_entry_filesize($xmlfile));
                        if (!tool_uclarolesmigration_cleanxml::is_valid_preset($xmlstring)) {
                            $errors['importfile'] = get_string('error_invalidxml', 'tool_uclarolesmigration');
                        }
                        zip_entry_close($xmlfile);
                    }
                }
                zip_close($zip);
            }

            // Clean up the zip file.
            unlink($zippath);
        } else {
            $errors['importfile'] = get_string('error_nofile', 'tool_uclarolesmigration');
        }
        return $errors;
    }
}
