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
 * Search area for library music reserves.
 *
 * @package    block_ucla_media
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_ucla_media\search;

defined('MOODLE_INTERNAL') || die();

/**
 * Search area for library music reserves.
 *
 * @package    block_ucla_media
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class library_music_reserves extends \core_search\base {

    /**
     * Returns true if this area uses file indexing.
     *
     * @return bool
     */
    public function uses_file_indexing() {
        return true;
    }

    /**
     * Returns a recordset containing all items from this area, optionally within the given context,
     * and including only items modifed from (>=) the specified time. The recordset must be ordered
     * in ascending order of modified time.
     *
     * @param int $modifiedfrom Return only records modified after this date
     * @param \context|null $context Context (null means no context restriction)
     * @return \moodle_recordset|null|false Recordset / null if no results / false if not supported
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
                    $contextrestriction = " AND lmr.courseid = ?";
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

        $sql = "SELECT lmr.*
                  FROM {ucla_library_music_reserves} lmr
                 WHERE lmr.timemodified >= ? $contextrestriction
              ORDER BY lmr.timemodified ASC";
        return $DB->get_recordset_sql($sql, $contextparams);
    }

    /**
     * Returns the document related with the provided record.
     *
     * @param \stdClass $record A record containing, at least, the indexed document id and a modified timestamp
     * @param array     $options Options for document creation
     * @return \core_search\document
     */
    public function get_document($record, $options = array()) {
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

        $doc = \core_search\document_factory::instance($record->id, $this->componentname, $this->areaname);
        $doc->set('title', content_to_text($record->title, false));
        $doc->set('content', content_to_text($record->albumtitle, false));
        $doc->set('contextid', $context->id);
        $doc->set('type', \core_search\manager::TYPE_TEXT);
        $doc->set('courseid', $record->courseid);
        $doc->set('modified', $record->timemodified);
        $doc->set('owneruserid', \core_search\manager::NO_OWNER_ID);

        if (isset($options['lastindexedtime']) && ($options['lastindexedtime'] < $record->timecreated)) {
            $doc->set_is_new(true);
        }

        return $doc;
    }

    /**
     * Can the current user see the document.
     *
     * @param int $id The internal search area entity id.
     * @return int manager:ACCESS_xx constant
     */
    public function check_access($id) {
        global $DB;

        $sql = "SELECT x.id as contextid
                  FROM {ucla_library_music_reserves} lmr
                  JOIN {course} c ON c.id = lmr.courseid
                  JOIN {context} x ON x.instanceid = c.id AND x.contextlevel = ?
                 WHERE lmr.id = ?";
        $params = [CONTEXT_COURSE, $id];
        $instance = $DB->get_record_sql($sql, $params);
        $context = \context::instance_by_id($instance->contextid);

        if (!$instance) {
            return \core_search\manager::ACCESS_DELETED;
        }
        if (!is_enrolled($context) && !has_capability('moodle/course:view', $context)) {
            return \core_search\manager::ACCESS_DENIED;
        }

        return \core_search\manager::ACCESS_GRANTED;
    }

    /**
     * Returns a url to the document.
     *
     * @param \core_search\document $doc
     * @return \moodle_url
     */
    public function get_doc_url(\core_search\document $doc) {
        return new \moodle_url('/blocks/ucla_media/libalbum.php', array('courseid' => $doc->get('courseid'),
                'albumid' => $doc->get('itemid'), 'title' => $doc->get('content')));
    }

    /**
     * Returns a url to the document context.
     *
     * @param \core_search\document $doc
     * @return \moodle_url
     */
    public function get_context_url(\core_search\document $doc) {
        return new \moodle_url('/blocks/ucla_media/libalbum.php', array('courseid' => $doc->get('courseid'),
                'albumid' => $doc->get('itemid'), 'title' => $doc->get('content')));
    }

    /**
     * Gets a list of all contexts to reindex when reindexing this search area.
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
                  FROM {ucla_library_music_reserves} lmr
                  JOIN {context} x ON x.instanceid = lmr.courseid AND x.contextlevel = ?
              GROUP BY $groupbycolumns
              ORDER BY MAX(lmr.timemodified) DESC", [CONTEXT_COURSE]);
        return new \core\dml\recordset_walk($rs, function($rec) {
            $id = $rec->ctxid;
            \context_helper::preload_from_record($rec);
            return \context::instance_by_id($id);
        });
    }
}
