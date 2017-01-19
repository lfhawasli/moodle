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

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../moodleblock.class.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_browseby/renderer.php');
require_once($CFG->dirroot . '/blocks/ucla_browseby/'
    . 'browseby_handler_factory.class.php');
require_once($CFG->dirroot . '/' . $CFG->admin 
    . '/tool/uclacoursecreator/uclacoursecreator.class.php');
require_once($CFG->dirroot . '/blocks/navigation/renderer.php');
require_once($CFG->dirroot . '/blocks/navigation/block_navigation.php');

class block_ucla_browseby extends block_navigation {
    var $termslist = array();

    function init() {
        $this->title = get_string('displayname', 'block_ucla_browseby');
        $this->content_type = BLOCK_TYPE_TEXT;
    }

    /**
     *  This is called in the course where the block has been added to.
     **/
    function get_content() {
        global $CFG;
        
        if (is_null($this->content)) {
            $this->content = new stdClass();
        }       

        $link_types = browseby_handler_factory::get_available_types();

        $blockconfig = get_config('block_ucla_browseby');

        $elements = array();
        
        foreach ($link_types as $link_type) {
            if (empty($blockconfig->{'disable_' . $link_type})) {
                $elements[] = navigation_node::create(
                    get_string('link_' . $link_type, 'block_ucla_browseby'),
                    new moodle_url(
                        $CFG->wwwroot . '/blocks/ucla_browseby/view.php',
                        array('type' => $link_type)
                    ), navigation_node::TYPE_SECTION
                );
            }
        }

        $renderer = $this->page->get_renderer('block_ucla_browseby');
        
        $this->content->text = $renderer->navigation_node($elements,
            array('class' => 'block_tree list'));
        
        return $this->content;
    }

    function instance_allow_config() {
        return false;
    }

    function instance_allow_multiple() {
        return false;
    }

    /**
     * Prevent block from being collapsed.
     *
     * @return bool
     */
    public function instance_can_be_collapsed() {
        return false;
    }

    function html_attributes() {
        $orig = parent::html_attributes();
        $orig['class'] .= ' block_ucla_course_menu block_navigation';

        return $orig;
    }
    /**
     *  Returns the applicable places that this block can be added.
     **/
    function applicable_formats() {
        return array(
            'site-index' => true,
            'course-view' => false,
            'my' => true
        );
    }

    /**
     *  Determines the terms to run the cron job for if there were no
     *  specifics provided.
     **/
    function guess_terms() {
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
     *  Figures out terms and run sync.
     **/
    function run_sync() {
        $this->guess_terms();

        if (empty($this->termslist)) {
            return true;
        }

        return $this->sync($this->termslist);
    }

    function cron() {
        $result = false;
        try {
            $result = $this->run_sync();
        } catch(Exception $e) {
            // mostly likely couldn't connect to registrar
            mtrace($e->getMessage());
        }
        return $result;        
    }

    function sync($terms, $subjareas=null) {
        // Don't run during unit tests. Can be triggered via
        // course_creator_finished event.
        if (defined('PHPUNIT_TEST') and PHPUNIT_TEST) {
            return true;
        }

        self::ucla_require_registrar();

        if (empty($terms)) {
            echo 'no terms specified for browseby cron' . "\n";
            return true;
        }

        echo "\n";

        list($sqlin, $params) = $this->get_in_or_equal($terms);
        $where = 'term ' . $sqlin;

        $records = array();
        if (empty($subjareas)) {
            // No subject area passed, so get list of subject areas from built courses.
            $records = $this->get_recordset_select('ucla_reg_classinfo',
                $where, $params, '', 'DISTINCT CONCAT(term, subj_area), term, '
                    . 'subj_area AS subjarea');
            // Check that there are records
            if (!($records && $records->valid())) {
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

        // Collect data from registrar, sync to local db
        foreach ($records as $record) {
            $term = $record->term;
            $subjarea = $record->subjarea;

            echo "Handling $term $subjarea...";

            $thisreg = array('term' => $term, 
                'subjarea' => $subjarea);

            $courseinfo = $this->run_registrar_query(
                'ccle_coursegetall', $thisreg);

            if ($courseinfo) {
                foreach ($courseinfo as $key => $ci) {
                    $ci['term'] = $term;
                    $courseinfo[$key] = $ci;
                }
            } else {
                echo "no course data...";
            }

            $instrinfo = $this->run_registrar_query(
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
            $res = $this->partial_sync_table('ucla_browseall_classinfo', $courseinfo,
                array('term', 'srs'), $where, $params);

            // + inserted records
            // = updated records
            // - deleted records
            echo '+' . count($res[0]) . ' =' . count($res[1]) . ' -' . count($res[2]) . '...';

            echo "sync instrinfo ";
            $res = $this->partial_sync_table('ucla_browseall_instrinfo', $instrinfo,
                array('term', 'srs', 'uid'), $where, $params);

            echo '+' . count($res[0]) . ' =' . count($res[1]) . ' -' . count($res[2]) . '...';

            echo "done.\n";
        }


        echo "Finished sync.\n";
            
        return true;
    }
    
    function get_all_terms() {
        global $DB;

        $termobjs = $this->get_records('ucla_request_classes', null, '',
            'DISTINCT term');

        $terms = array();
        foreach ($termobjs as $termobj) {
            $terms[] = $termobj->term;
        }

        return $terms;
    }

    static function add_to_frontpage() {
        global $SITE;
        $fakepage = new moodle_page();
        $fakepage->set_course($SITE);
        $fakepage->set_pagelayout('frontpage');
        $fakepage->set_pagetype('site-index');
        $bm =& $fakepage->blocks;
        $bm->load_blocks();
        $bm->create_all_block_instances();
        if (!$bm->is_block_present('ucla_browseby')) {
            $bm->add_block('ucla_browseby', BLOCK_POS_LEFT, 0, false); 
            // There's no API to guarantee that this was successful :D
        }
    }

    /**
     *  Decoupled functions
     **/
    protected static function ucla_require_registrar() {
        ucla_require_registrar();
    }

    protected function partial_sync_table($table, $tabledata, $syncfields,
            $partialwhere=null, $partialparams=null) {
        ucla_require_db_helper();

        return db_helper::partial_sync_table($table, $tabledata, $syncfields,
            $partialwhere, $partialparams);
    }

    protected function get_in_or_equal($vars) {
        global $DB;

        return $DB->get_in_or_equal($vars);
    }

    protected function get_recordset_select($t, $w, $p, $s, $l) {
        global $DB;

        return $DB->get_recordset_select($t, $w, $p, $s, $l);
    }
   
    protected function run_registrar_query($q, $d) {
        return registrar_query::run_registrar_query($q, $d);
    }
    protected function get_records($t, $p, $o, $s) {
        global $DB;

        return $DB->get_records($t, $p, $o, $s);
    }

}

/** eof **/
