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
 * Q&A entries search area.
 *
 * @package    mod_qanda
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_qanda\search;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/qanda/lib.php');

/**
 * Q&A entries search area.
 *
 * @package    mod_qanda
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entry extends \core_search\base_mod {

    /**
     * @var array Internal quick static cache.
     */
    protected $qandasdata = array();

    /**
     * @var array Internal quick static cache.
     */
    protected $entriesdata = array();

    /**
     * Returns recordset containing required data for indexing q&a entries.
     *
     * @param int $modifiedfrom timestamp
     * @param \context|null $context Optional context to restrict scope of returned results
     * @return moodle_recordset|null Recordset (or null if no results)
     */
    public function get_document_recordset($modifiedfrom = 0, \context $context = null) {
        global $DB;

        list ($contextjoin, $contextparams) = $this->get_context_restriction_sql(
                $context, 'qanda', 'q');
        if ($contextjoin === null) {
            return null;
        }

        $sql = "SELECT qe.*, q.name as qandaname, q.course AS courseid
                  FROM {qanda_entries} qe
                  JOIN {qanda} q ON q.id = qe.qandaid
          $contextjoin
                 WHERE qe.timemodified >= ? ORDER BY qe.timemodified ASC";
        return $DB->get_recordset_sql($sql, array_merge($contextparams, [$modifiedfrom]));
    }

    /**
     * Returns the document associated with this entry id.
     *
     * @param stdClass $record Entry info.
     * @param array    $options
     * @return \core_search\document
     */
    public function get_document($record, $options = array()) {

        try {
            $cm = $this->get_cm('qanda', $record->qandaid, $record->courseid);
            $context = \context_module::instance($cm->id);
        } catch (\dml_missing_record_exception $ex) {
            // Notify it as we run here as admin, we should see everything.
            debugging('Error retrieving ' . $this->areaid . ' ' . $record->id . ' document, not all required data is available: ' .
                $ex->getMessage(), DEBUG_DEVELOPER);
            return false;
        } catch (\dml_exception $ex) {
            // Notify it as we run here as admin, we should see everything.
            debugging('Error retrieving ' . $this->areaid . ' ' . $record->id . ' document: ' . $ex->getMessage(), DEBUG_DEVELOPER);
            return false;
        }

        // Concatenate the question and the answer.
        $content = "Q: ".$record->question." A: ".$record->answer;

        // Prepare associative array with data from DB.
        $doc = \core_search\document_factory::instance($record->id, $this->componentname, $this->areaname);
        $doc->set('title', content_to_text($record->qandaname, false));
        $doc->set('content', content_to_text($content, $record->questionformat));
        $doc->set('contextid', $context->id);
        $doc->set('courseid', $record->courseid);
        $doc->set('userid', $record->userid);
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
     * Whether the user can access the document or not.
     *
     * @throws \dml_missing_record_exception
     * @throws \dml_exception
     * @param int $id Q&A entry id
     * @return bool
     */
    public function check_access($id) {
        global $USER;

        try {
            $entry = $this->get_entry($id);
            $qanda = $this->get_qanda($entry->qandaid);
            $cminfo = $this->get_cm('qanda', $qanda->id, $qanda->course);
            $cm = $cminfo->get_course_module_record();
        } catch (\dml_missing_record_exception $ex) {
            return \core_search\manager::ACCESS_DELETED;
        } catch (\dml_exception $ex) {
            return \core_search\manager::ACCESS_DENIED;
        }

        // Recheck uservisible although it should have already been checked in core_search.
        if ($cminfo->uservisible === false) {
            return \core_search\manager::ACCESS_DENIED;
        }

        return \core_search\manager::ACCESS_GRANTED;
    }

    /**
     * Link to the q&a entry.
     *
     * @param \core_search\document $doc
     * @return \moodle_url
     */
    public function get_doc_url(\core_search\document $doc) {
        $contextmodule = \context::instance_by_id($doc->get('contextid'));
        return new \moodle_url('/mod/qanda/view.php', array('id' => $contextmodule->instanceid));
    }

    /**
     * Link to the q&a.
     *
     * @param \core_search\document $doc
     * @return \moodle_url
     */
    public function get_context_url(\core_search\document $doc) {
        $contextmodule = \context::instance_by_id($doc->get('contextid'));
        return new \moodle_url('/mod/qanda/view.php', array('id' => $contextmodule->instanceid));
    }

    /**
     * Returns the specified q&a entry from its internal cache.
     *
     * @throws \dml_missing_record_exception
     * @param int $entryid
     * @return stdClass
     */
    protected function get_entry($entryid) {
        global $DB;
        if (empty($this->entriesdata[$entryid])) {
            $this->entriesdata[$entryid] = $DB->get_record('qanda_entries', array('id' => $entryid), '*', MUST_EXIST);
            if (!$this->entriesdata[$entryid]) {
                throw new \dml_missing_record_exception('qanda_entries');
            }
        }
        return $this->entriesdata[$entryid];
    }

    /**
     * Returns the specified q&a checking the internal cache.
     *
     * Store minimal information as this might grow.
     *
     * @throws \dml_exception
     * @param int $qandaid
     * @return stdClass
     */
    protected function get_qanda($qandaid) {
        global $DB;

        if (empty($this->qandasdata[$qandaid])) {
            $this->qandasdata[$qandaid] = $DB->get_record('qanda', array('id' => $qandaid), '*', MUST_EXIST);
        }
        return $this->qandasdata[$qandaid];
    }

    /**
     * Confirms that data entries support group restrictions.
     *
     * @return bool True
     */
    public function supports_group_restriction() {
        return true;
    }
}
