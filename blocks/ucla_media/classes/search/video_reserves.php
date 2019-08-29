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
 * Search area for block_ucla_media video reserves.
 *
 * @package block_ucla_media
 * @copyright 2019 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_ucla_media\search;

use core_search\moodle_recordset;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/ucla_media/locallib.php');
require_once($CFG->dirroot . '/lib/weblib.php');

/**
 * Search area for block_ucla_media video reserves.
 *
 * @package block_ucla_media
 * @copyright 2019 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class video_reserves extends \core_search\base {

    /**
     * Returns true if this area uses file indexing.
     *
     * @return bool
     */
    public function uses_file_indexing() {
        return true;
    }

    /**
     * Returns recordset containing required data for indexing video reserves.
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
                    $contextrestriction = " AND vr.courseid = ?";
                    $contextparams[] = $context->instanceid;
                    break;

                case CONTEXT_COURSECAT:
                case CONTEXT_MODULE:
                case CONTEXT_BLOCK:
                case CONTEXT_USER:
                    return null;

                default:
                    throw new \coding_exception('Unexpected contextlevel: ' . $context->contextlevel);
            }
        }

        $sql = "SELECT vr.*, c.shortname
                  FROM {ucla_video_reserves} vr
                  JOIN {course} c ON vr.courseid = c.id
                 WHERE vr.timemodified >= ? $contextrestriction
              ORDER BY vr.timemodified ASC";

        return $DB->get_recordset_sql($sql, $contextparams);
    }

    /**
     * Returns the document associated with this video reserve id.
     *
     * @param stdClass $record video reserve info.
     * @param array    $options
     * @return \core_search\document
     */
    public function get_document($record, $options = array()) {
        // Create empty document.
        $doc = \core_search\document_factory::instance($record->id,
                $this->componentname, $this->areaname);

        // Get content.
        $content = $record->shortname .". ";
        $content .= date("m/d/Y", $record->start_date) ." - ". date("m/d/Y", $record->stop_date);

        // Get context.
        try {
            $context = \context_course::instance($record->courseid);
        } catch (\dml_missing_record_exception $ex) {
            debugging('Error retrieving ' . $this->areaid . ' ' . $record->id . ' document, not all required data is available: ' .
                    $ex->getMessage(), DEBUG_DEVELOPER);
            return false;
        } catch (\dml_exception $ex) {
            debugging('Error retrieving ' . $this->areaid . ' ' . $record->id . ' document: ' . $ex->getMessage(), DEBUG_DEVELOPER);
            return false;
        }

        $doc->set('title', content_to_text($record->video_title, false));
        $doc->set('content', content_to_text($content, false));
        $doc->set('contextid', $context->id);
        $doc->set('type', \core_search\manager::TYPE_TEXT);
        $doc->set('courseid', $record->courseid);
        $doc->set('modified', $record->timemodified);
        $doc->set('owneruserid', \core_search\manager::NO_OWNER_ID);

        // Mark document new if appropriate.
        if (isset($options['lastindexedtime']) && ($options['lastindexedtime'] < $record->timecreated)) {
            // If the document was created after the last index time, it must be new.
            $doc->set_is_new(true);
        }

        return $doc;
    }

    /**
     * Whether the user can access the document or not.
     *
     * @param int $id Video reserve id
     * @return bool
     */
    public function check_access($id) {
        global $DB;

        $sql = "SELECT x.id as contextid, vr.start_date, vr.stop_date
                  FROM {ucla_video_reserves} vr
                  JOIN {course} c ON c.id = vr.courseid
                  JOIN {context} x ON x.instanceid = c.id AND x.contextlevel = ?
                 WHERE vr.id = ?";
        $params = [CONTEXT_COURSE, $id];
        $instance = $DB->get_record_sql($sql, $params);
        $context = \context::instance_by_id($instance->contextid);

        if (!$instance) {
            return \core_search\manager::ACCESS_DELETED;
        }
        if (!is_enrolled($context) && !has_capability('moodle/course:view', $context)) {
            return \core_search\manager::ACCESS_DENIED;
        }
        if ($instance->start_date > time() || $instance->stop_date < time()) {
            return \core_search\manager::ACCESS_DENIED;
        }
        return \core_search\manager::ACCESS_GRANTED;
    }

    /**
     * Link to the video reserve.
     *
     * @param \core_search\document $doc
     * @return \moodle_url
     */
    public function get_doc_url(\core_search\document $doc) {
        return new \moodle_url('/blocks/ucla_media/view.php',
            array('id' => $doc->get('itemid'), 'mode' => MEDIA_VIDEORESERVES));
    }

    /**
     * Link to Video reserves page.
     *
     * @param \core_search\document $doc
     * @return \moodle_url
     */
    public function get_context_url(\core_search\document $doc) {
        $context = \context::instance_by_id($doc->get('contextid'));
        return new \moodle_url('/blocks/ucla_media/videoreserves.php', array('courseid' => $context->instanceid));
    }

    /**
     * Gets a list of all contexts to reindex when reindexing this search area.
     *
     * @return \Iterator Iterator of contexts to reindex
     * @throws \moodle_exception If any DB error
     */
    public function get_contexts_to_reindex() {
        global $DB;

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
                  FROM {ucla_video_reserves} vr
                  JOIN {context} x ON x.instanceid = vr.courseid AND x.contextlevel = ?
              GROUP BY $groupbycolumns
              ORDER BY MAX(vr.timemodified) DESC", [CONTEXT_COURSE]);
        return new \core\dml\recordset_walk($rs, function($rec) {
            $id = $rec->ctxid;
            \context_helper::preload_from_record($rec);
            return \context::instance_by_id($id);
        });
    }
}
