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
 * Syllabus search area.
 *
 * @package    local_ucla
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ucla_syllabus\search;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/ucla_syllabus/locallib.php');

/**
 * Syllabus search area.
 *
 * @package    local_ucla
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class syllabus extends \core_search\base {

    /**
     * The context levels the search area is working on.
     *
     * @var array
     */
    protected static $levels = [CONTEXT_COURSE];

    /**
     * @var array Internal quick static cache.
     */
    protected $syllabusdata = array();

    /**
     * Gets a list of all contexts to reindex when reindexing this search area.
     *
     * This returns the course contexts for all courses that contain a syllabus, in
     * order of time the syllabus was last modified (most recent first).
     *
     * @return \Iterator Iterator of contexts to reindex
     * @throws \moodle_exception If any DB error
     */
    public function get_contexts_to_reindex() {
        global $DB;

        $contexts = [];
        $selectcolumns = \context_helper::get_preload_record_columns_sql('x');
        $groupbycolumns = '';
        foreach (\context_helper::get_preload_record_columns('x') as $column => $thing) {
            if ($groupbycolumns !== '') {
                $groupbycolumns .= ',';
            }
            $groupbycolumns .= $column;
        }
        $rs = $DB->get_recordset_sql("
                SELECT $selectcolumns
                    FROM {ucla_syllabus} s
                    JOIN {course} c ON c.id = s.courseid
                    JOIN {context} x ON x.instanceid = c.id AND x.contextlevel = ?
                GROUP BY $groupbycolumns
                ORDER BY MAX(s.timemodified) DESC", [CONTEXT_COURSE]);
        return new \core\dml\recordset_walk($rs, function($rec) {
            $id = $rec->ctxid;
            \context_helper::preload_from_record($rec);
            return \context::instance_by_id($id);
        });
    }

    /**
     * Returns recordset containing required data for indexing syllabi.
     *
     * @param int $modifiedfrom timestamp
     * @param \context|null $context Optional context to restrict scope of returned results
     * @return moodle_recordset|null Recordset (or null if no results)
     */
    public function get_document_recordset($modifiedfrom = 0, \context $context = null) {
        global $DB;

        $contextrestriction = '';
        $contextparams = [$modifiedfrom];

        if ($context) {
            switch ($context->contextlevel) {
                case CONTEXT_SYSTEM:
                    break;

                case CONTEXT_COURSE:
                    // Find all syllabi belonging to the course.
                    $contextrestriction = " AND s.courseid = ?";
                    $contextparams[] = $context->instanceid;
                    break;

                case CONTEXT_MODULE:
                case CONTEXT_BLOCK:
                case CONTEXT_USER:
                    // These contexts cannot contain syllabi, so return null.
                    return null;

                default:
                    throw new \coding_exception('Unexpected contextlevel: ' . $context->contextlevel);
            }
        }

        $sql = "SELECT s.* FROM {ucla_syllabus} s
                WHERE s.timemodified >= ? $contextrestriction
                ORDER BY s.timemodified ASC;";
        return $DB->get_recordset_sql($sql, $contextparams);
    }

    /**
     * Returns the document associated with this syllabus id.
     *
     * @param stdClass $record Syllabus info.
     * @param array    $options
     * @return \core_search\document
     */
    public function get_document($record, $options = array()) {
        global $DB;

        if (!$DB->record_exists('ucla_syllabus', array('courseid' => $record->courseid))) {
            debugging('Error retrieving ' . $this->areaid . ' ' . $record->id . ' document, not all required data is available: ' .
                $ex->getMessage(), DEBUG_DEVELOPER);
            return false;
        }

        $context = \context_course::instance($record->courseid);

        // Prepare associative array with data from DB.
        $doc = \core_search\document_factory::instance($record->id, $this->componentname, $this->areaname);
        $doc->set('title', content_to_text($record->display_name, false));
        $doc->set('content', content_to_text('', false));
        $doc->set('contextid', $context->id);
        $doc->set('courseid', $record->courseid);
        $doc->set('owneruserid', \core_search\manager::NO_OWNER_ID);
        $doc->set('modified', $record->timemodified);

        // Check if this document should be considered new.
        if (isset($options['lastindexedtime']) && ($options['lastindexedtime'] < $record->timecreated)) {
            // If the document was created after the last index time, it must be new.
            $doc->set_is_new(true);
        }

        return $doc;
    }

    /**
     * Returns true if this area uses file indexing.
     *
     * @return bool
     */
    public function uses_file_indexing() {
        return true;
    }

    /**
     * Add the syllabus file.
     *
     * @param document $document The current document
     * @return null
     */
    public function attach_files($document) {
        global $DB;

        $syllabusid = $document->get('itemid');

        try {
            $syllabus = \ucla_syllabus_manager::instance($syllabusid, MUST_EXIST);
        } catch (\dml_missing_record_exception $e) {
            debugging('Could not get record to attach files to '.$document->get('id'), DEBUG_DEVELOPER);
            return;
        }

        $file = $syllabus->locate_syllabus_file();
        $document->add_stored_file($file);
    }

    /**
     * Whether the user can access the document or not.
     *
     * @throws \dml_missing_record_exception
     * @throws \dml_exception
     * @param int $id Syllabus id
     * @return bool
     */
    public function check_access($id) {
        global $DB, $USER;

        try {
            $syllabus = \ucla_syllabus_manager::instance($id, MUST_EXIST);
        } catch (\dml_missing_record_exception $ex) {
            return \core_search\manager::ACCESS_DELETED;
        } catch (\dml_exception $ex) {
            return \core_search\manager::ACCESS_DENIED;
        }

        // Check if user has access to this syllabus.
        if (!$syllabus->can_view()) {
            return \core_search\manager::ACCESS_DENIED;
        }

        // Don't show a non-private syllabus for a course if it has a private syllabus the user can access.
        if ($syllabus->access_type != UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE) {
            // Use a syllabus manager to get all syllabi for the course so we can check if there is a private one.
            $course = $DB->get_record('course', array('id' => $syllabus->courseid), '*', MUST_EXIST);
            $syllabusmanager = new \ucla_syllabus_manager($course);
            $syllabi = $syllabusmanager->get_syllabi();
            if (!empty($syllabi[UCLA_SYLLABUS_TYPE_PRIVATE]) && $syllabi[UCLA_SYLLABUS_TYPE_PRIVATE]->can_view()) {
                    return \core_search\manager::ACCESS_DENIED;
            }
        }

        return \core_search\manager::ACCESS_GRANTED;
    }

    /**
     * Link to the syllabus page.
     *
     * @param \core_search\document $doc
     * @return \moodle_url
     */
    public function get_doc_url(\core_search\document $doc) {
        return new \moodle_url('/local/ucla_syllabus/index.php', array('id' => $doc->get('courseid')));
    }

    /**
     * Link to the syllabus page.
     *
     * @param \core_search\document $doc
     * @return \moodle_url
     */
    public function get_context_url(\core_search\document $doc) {
        return new \moodle_url('/local/ucla_syllabus/index.php', array('id' => $doc->get('courseid')));
    }

    /**
     * Returns the specified syllabus from its internal cache.
     *
     * @throws \dml_missing_record_exception
     * @param int $syllabusid
     * @return stdClass
     */
    protected function get_syllabus($syllabusid) {
        global $DB;

        if (empty($this->syllabusdata[$syllabusid])) {
            $this->syllabusdata[$syllabusid] = $DB->get_record('ucla_syllabus', array('id' => $syllabusid), '*', MUST_EXIST);
        }
        return $this->syllabusdata[$syllabusid];
    }
}