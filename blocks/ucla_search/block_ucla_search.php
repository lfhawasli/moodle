<?php
// This file is part of the UCLA Search block for Moodle - http://moodle.org/
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
 * UCLA search block file.
 *
 * @package     block_ucla_search
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright   2018 UC Regents
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/moodleblock.class.php');

/**
 * Block file.
 *
 * @package     block_ucla_search
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright   2018 UC Regents
 */
class block_ucla_search extends block_base {

    /**
     * Initialize block.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_search');
    }

    /**
     * Returns block contents.
     *
     * @return object.
     */
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        // Write content.
        $this->content->text = self::search_form();

        return $this->content;
    }

    /**
     * Where the block can be added.
     *
     * @return array
     */
    public function applicable_formats() {
        return array(
            'site-index' => true,
            'course-view' => false,
            'my' => true,
        );
    }

    /**
     * Print advanced search form html for various components.  Compatible
     * with default moodle search if javascript is off.
     *
     * @param string $type
     * @param array $searchparams
     * @return string
     */
    public static function search_form($type = 'block-search', $searchparams = null) {
        global $CFG, $OUTPUT, $PAGE;

        // Load YUI module.
        $PAGE->requires->yui_module('moodle-block_ucla_search-search',
                'M.block_ucla_search.search.init', array($type));

        // Default.
        $templatecontext = array('type' => $type);
        $templatecontext['displaytitle'] = false;
        $templatecontext['checkcollab'] = true;
        $templatecontext['checkcourse'] = true;
        $templatecontext['checkbytitle'] = true;
        $templatecontext['checkbydescription'] = true;

        switch ($type) {
            case 'frontpage-search':
            case 'block-search':
                $templatecontext['displaytitle'] = true;
                break;
            case 'course-search':
                $templatecontext['checkcollab'] = false;
                break;
            case 'collab-search':
                $templatecontext['checkcourse'] = false;
                break;
        }

        // If search params were passed, then need to retain those settings.
        $searchterm = null;
        if (!empty($searchparams)) {
            if (isset($searchparams['collab'])) {
                $templatecontext['checkcollab'] = $searchparams['collab'];
            }
            if (isset($searchparams['course'])) {
                $templatecontext['checkcourse'] = $searchparams['course'];
            }
            if (isset($searchparams['bytitle'])) {
                $templatecontext['checkbytitle'] = $searchparams['bytitle'];
            }
            if (isset($searchparams['bydescription'])) {
                $templatecontext['checkbydescription'] = $searchparams['bydescription'];
            }
            if (!empty($searchparams['search'])) {
                $templatecontext['searchterm'] = $searchparams['search'];
            }
        }

        $templatecontext['formurl'] = $CFG->wwwroot . '/course/search.php';

        return $OUTPUT->render_from_template('block_ucla_search/search_box', $templatecontext);
    }

    /**
     * Prevent block from being collapsed.
     *
     * @return bool
     */
    public function instance_can_be_collapsed() {
        return false;
    }

}
