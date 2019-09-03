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
 * UCLA core search renderer and overrides core_search renderer.
 *
 * @package    theme_uclashared
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace theme_uclashared\output;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/search/classes/output/renderer.php');

/**
 * UCLA core search renderer and overrides core_search renderer.
 *
 * @package    theme_uclashared
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class core_search_renderer extends \core_search\output\renderer {
    /**
     * Displaying search results.
     *
     * @param \core_search\document Containing a single search response to be displayed.
     * @param string Containing the search query to be highlighted.
     * @return string HTML
     */
    public function render_result(\core_search\document $doc, string $query) {
        $docdata = $doc->export_for_template($this);

        // Limit text fields size.
        $docdata['title'] = shorten_text($docdata['title'], static::SEARCH_RESULT_STRING_SIZE, true);
        $docdata['content'] = $docdata['content'] ? shorten_text($docdata['content'], static::SEARCH_RESULT_TEXT_SIZE, true) : '';
        $docdata['description1'] = $docdata['description1'] ? shorten_text($docdata['description1'], static::SEARCH_RESULT_TEXT_SIZE, true) : '';
        $docdata['description2'] = $docdata['description2'] ? shorten_text($docdata['description2'], static::SEARCH_RESULT_TEXT_SIZE, true) : '';

        // Highlight the query if present in the title and content of each search result.
        // Convert all applicable characters to their HTML equivalents so they are highlighted correctly.
        $query = htmlentities($query, ENT_QUOTES, 'UTF-8');
        $docdata['title'] = highlightfast($query, $docdata['title']);
        $docdata['content'] = highlightfast($query, $docdata['content']);

        return $this->output->render_from_template('core_search/result', $docdata);
    }
}