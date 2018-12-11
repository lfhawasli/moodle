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
 * BrowseBy class.
 *
 * @package    block_ucla_browseby
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_browseby/'
    . 'browseby_handler_factory.class.php');
require_once($CFG->dirroot . '/' . $CFG->admin
    . '/tool/uclacoursecreator/uclacoursecreator.class.php');

/**
 * Class for BrowseBy.
 *
 * @package    block_ucla_browseby
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_browseby extends block_base {
    /**
     * @var $termslist List of terms.
     */
    public $termslist = array();

    /**
     * Set the initial properties for the block.
     */
    public function init() {
        $this->title = get_string('displayname', 'block_ucla_browseby');
        $this->content_type = BLOCK_TYPE_TEXT;
    }

    /**
     * Returns browseby links.
     */
    public function get_content() {
        global $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';
       
        $linktypes = browseby_handler_factory::get_available_types();
        $blockconfig = get_config('block_ucla_browseby');
        $templatecontext = array();
        $skipactivecheck = false;   // There will only be at most 1 active link.
        foreach ($linktypes as $linktype) {
            if (empty($blockconfig->{'disable_' . $linktype})) {
                $element = array();
                $element['link'] = new moodle_url('/blocks/ucla_browseby/view.php',
                        array('type' => $linktype));
                $element['name'] = get_string('link_' . $linktype, 'block_ucla_browseby');

                // See if we are on the page we are linking to.
                if (!$skipactivecheck && $PAGE->url->compare($element['link'], URL_MATCH_BASE)) {
                    // We are in BrowseBy change. Check type param.
                    $type = $PAGE->url->get_param('type');
                    if ($type == $linktype ||
                            ($linktype == 'subjarea' && $type == 'course')) {
                        $element['active'] = true;
                        $skipactivecheck = true;
                    }
                } else {
                   $skipactivecheck = true; // We are not on BrowseBy.
                }

                $templatecontext['links'][] = $element;
            }
        }

        $renderer = $PAGE->get_renderer('block_ucla_browseby');
        $this->content->text = $renderer->render_from_template(
                'block_ucla_browseby/block_content', $templatecontext);

        return $this->content;
    }

    /**
     * Prevent instance configuration.
     *
     * @return boolean
     */
    public function instance_allow_config() {
        return false;
    }

    /**
     * All multiple instances of this block.
     *
     * @return bool Returns false
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Prevent block from being collapsed.
     *
     * @return bool Returns false
     */
    public function instance_can_be_collapsed() {
        return false;
    }

    /**
     * Returns the applicable places that this block can be added.
     */
    public function applicable_formats() {
        return array(
            'site-index' => true,
            'course-view' => false,
            'my' => true
        );
    }

    /**
     * Determines the terms to run the cron job for if there were no
     * specifics provided.
     */
    public function guess_terms() {
        global $CFG;

        if (!empty($this->termslist)) {
            return;
        }

        $this->termslist = array($CFG->currentterm);

        if (get_config('block_ucla_browseby', 'syncallterms')) {
            $this->termslist = $this->get_all_terms();

            set_config('syncallterms', false, 'block_ucla_browseby');
        }
    }

    /**
     * Figures out terms and run sync.
     */
    public function run_sync() {
        $this->guess_terms();

        if (empty($this->termslist)) {
            return true;
        }

        return $this->sync($this->termslist);
    }

    /**
     * Execute this method when the cron runs.
     *
     * @return bool
     */
    public function cron() {
        $result = false;
        try {
            $result = $this->run_sync();
        } catch (Exception $e) {
            // Most likely couldn't connect to registrar.
            mtrace($e->getMessage());
        }
        return $result;
    }

    /**
     * Sync block.
     *
     * @param array $terms
     * @param array $subjareas
     * @return boolean
     */
    public function sync($terms, $subjareas=null) {
        global $DB;
        // Don't run during unit tests. Can be triggered via
        // course_creator_finished event.
        if (defined('PHPUNIT_TEST') and PHPUNIT_TEST) {
            return true;
        }

        ucla_require_registrar();
        ucla_require_db_helper();

        if (empty($terms)) {
            echo 'no terms specified for browseby cron' . "\n";
            return true;
        }

        echo "\n";

        list($sqlin, $params) = $DB->get_in_or_equal($terms);
        $where = 'term ' . $sqlin;

        $records = array();
        if (empty($subjareas)) {
            // No subject area passed, so get list of subject areas from built courses.
            $records = $DB->get_recordset_select('ucla_reg_classinfo',
                $where, $params, '', 'DISTINCT CONCAT(term, subj_area), term, '
                    . 'subj_area AS subjarea');
            // Check that there are records.
            if (!$records->valid()) {
                return true;
            }
        } else {
            // Subject areas passed. Use that in conjuction with terms.
            foreach ($terms as $term) {
                foreach ($subjareas as $subjarea) {
                    $record = new stdClass();
                    $record->term = $term;
                    $record->subjarea = $subjarea;
                    $records[] = $record;
                }
            }
        }

        // Collect data from registrar, sync to local db.
        foreach ($records as $record) {
            $term = $record->term;
            $subjarea = $record->subjarea;

            echo "Handling $term $subjarea...";

            $thisreg = array('term' => $term,
                'subjarea' => $subjarea);

            $courseinfo = registrar_query::run_registrar_query(
                'ccle_coursegetall', $thisreg);

            if ($courseinfo) {
                foreach ($courseinfo as $key => $ci) {
                    $ci['term'] = $term;
                    $courseinfo[$key] = $ci;
                }
            } else {
                echo "no course data...";
            }

            $instrinfo = registrar_query::run_registrar_query(
                'ccle_getinstrinfo', $thisreg);

            if ($instrinfo) {
                foreach ($instrinfo as $key => $ii) {
                    $ii['subjarea'] = $subjarea;
                    $instrinfo[$key] = $ii;
                }
            } else {
                echo "no instr data...";
            }

            $where = 'term = ? AND subjarea = ?';
            $params = array($term, $subjarea);

            // Save which courses need instructor informations.
            // We need to update the existing entries, and remove
            // non-existing ones.
            echo "sync classinfo ";
            $res = db_helper::partial_sync_table('ucla_browseall_classinfo', $courseinfo,
                array('term', 'srs'), $where, $params);

            // Denote + inserted records.
            // Denote = updated records.
            // Denote - deleted records.
            echo '+' . count($res[0]) . ' =' . count($res[1]) . ' -' . count($res[2]) . '...';

            echo "sync instrinfo ";
            $res = db_helper::partial_sync_table('ucla_browseall_instrinfo', $instrinfo,
                array('term', 'srs', 'uid'), $where, $params);

            echo '+' . count($res[0]) . ' =' . count($res[1]) . ' -' . count($res[2]) . '...';

            echo "done.\n";
        }

        // Purge the browse by cache.
        $cache = cache::make('block_ucla_browseby', 'browsebycache');
        $cache->purge();

        echo "Finished sync.\n";

        return true;
    }

    /**
     * Returns all distinct terms.
     *
     * @return array
     */
    public function get_all_terms() {
        global $DB;

        $terms = $DB->get_fieldset_select('ucla_request_classes', 'DISTINCT term', '');

        return $terms;
    }

}
