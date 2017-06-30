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
 * Deprecated. Use MoodleQuickForm_filepicker instead (/lib/form/filepicker.php).
 *
 * @package    block_ucla_easyupload
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/lib/pear/HTML/QuickForm/file.php');
require_once($CFG->dirroot . '/lib/form/filepicker.php');
// The following required file has been removed from Moodle 2.5:
// require_once($CFG->dirroot . '/lib/form/file.php');.

/**
 * This class extends MoodleQuickForm_file, which has been deprecated since Moodle 2.0.
 *
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class MoodleQuickForm_uclafile extends MoodleQuickForm_file {
    /**
     * Not really used anywhere.
     * @var object $_filepicker
     */
    private $_filepicker;
    /**
     * Not really used anywhere.
     * @var boolean $_draftid
     */
    private $_draftid = false;
    /**
     * Constructor for MoodleQuickForm_uclafile.
     * @param object $elname
     * @param object $ellabel
     * @param object $attr
     */
    public function __construct($elname=null, $ellabel=null, $attr=null) {
        parent::HTML_QuickForm_file($elname, $ellabel, $attr);
    }
}