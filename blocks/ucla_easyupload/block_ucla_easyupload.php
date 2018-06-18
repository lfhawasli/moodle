<?php
// This file is part of UCLA local plugin for Moodle - http://moodle.org/
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
 * UCLA easyupload's block. Note that the block cannot be added everywhere.
 *
 * @package    block_ucla_easyupload
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot.'/course/format/ucla/lib.php');

/**
 * UCLA easyupload extends block_base.
 *
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_easyupload extends block_base {
    /**
     * Initializes the title of the block, using the string in lang/en/block_ucla_easyupload.php.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_easyupload');
    }

    /**
     * Handle a physical file upload.
     * @param string $contextid
     */
    public static function upload($contextid) {
        global $CFG, $DB;

        $type = 'upload';
        $sql = "
            SELECT i.id, i.name
            FROM {repository} r
                INNER JOIN {repository_instances} i
                    ON i.typeid = r.id
            WHERE r.type = ?
        ";

        $repo = $DB->get_record_sql($sql, array($type));

        if (!$repo) {
            throw new moodle_exception();
        }

        $file = $CFG->dirroot . '/repository/' . $type . '/lib.php';

        if (file_exists($file)) {
            require_once($file);

            $classname = 'repository_' . $type;

            try {
                $repo = new $classname($repo->id, $contextid, array(
                    'ajax' => false,
                    'name' => $repo->name,
                    'type' => $type
                ));
            } catch (repository_exception $e) {
                print_error('pluginerror', 'repository');
            }
        } else {
            print_error('invalidplugin', 'repository');
        }

        $maxbytes = get_max_upload_file_size();

        return $repo->upload('', $maxbytes);
    }

    /**
     *  Checks if rearrange JS framework is available.
     */
    public static function block_ucla_rearrange_installed() {
        global $CFG;

        $rearrangepath = $CFG->dirroot
            . '/blocks/ucla_rearrange/block_ucla_rearrange.php';

        if (file_exists($rearrangepath)) {
            require_once($rearrangepath);
            return true;
        }

        return false;
    }

    /**
     * Convenience function to generate a variable assignment
     * statement in JavaScript.
     * TODO Might want to move this function to rearrange
     * @param string $var
     * @param string $val
     * @param boolean $quote
     * @return string
     */
    public static function js_variable_code($var, $val, $quote = true) {
        if ($quote) {
            $val = '"' . $val . '"';
        }

        return 'M.block_ucla_easyupload.' . $var . ' = ' . $val;
    }

    /**
     * Returns if the type specified has code to handle it.
     * @param string $type
     * @return string/boolean
     */
    public static function upload_type_exists($type) {
        global $CFG;

        $typelib = $CFG->dirroot
            . '/blocks/ucla_easyupload/upload_types/*.php';

        $possibles = glob($typelib);

        foreach ($possibles as $typefile) {
            require_once($typefile);
        }

        $typeclass = 'easyupload_' . $type . '_form';

        if (class_exists($typeclass)) {
            return $typeclass;
        }

        return false;
    }

    /**
     *  Do not allow block to be added anywhere
     */
    public function applicable_formats() {
        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false,
            'not-really-applicable' => true
        );
    }
}