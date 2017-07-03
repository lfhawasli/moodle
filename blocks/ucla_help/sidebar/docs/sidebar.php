<?php
// This file is part of the UCLA local help plugin for Moodle - http://moodle.org/
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
 * Sidebar for docs
 *
 * @package    block_ucla_help
 * @author     Rex Lorenzo <rex@seas.ucla.edu>
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Sidebar for docs
 *
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sidebar_docs extends sidebar_html implements sidebar_widget {

    /**
     * URL template for keyword query
     *
     * @var string $keywordquery
     */
    private $keywordquery = '?action=query&titles={title}&prop=links&format=json';
    /**
     * Constructs your object
     *
     * @param string $keyword
     */
    public function __construct($keyword) {
        global $PAGE;

        $PAGE->requires->js('/theme/uclashared/javascript/accordion.min.js');
        $PAGE->requires->yui_module('moodle-block_ucla_help-doc_loader', 'M.block_ucla_help.init_doc_loader',
                array(array('help' => '/blocks/ucla_help/help/gradebook')));

        $this->keyword = $keyword;
    }

    /**
     * Returns decoded JSON response from request
     *
     * @param string $url
     * @return object
     */
    private function curl($url) {
        $apiurl = get_config('block_ucla_help', 'docs_wiki_api');
        $url = $apiurl . $url;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }

    /**
     * Sends request to get topics
     *
     * @return array
     */
    private function get_topics() {
        $url = preg_replace('/{title}/', $this->keyword, $this->keywordquery);
        $response = $this->curl($url);

        if (empty($response)) {
            return array();
        }

        $keys = array_keys((array) $response->{'query'}->{'pages'});
        $pageid = array_pop($keys);
        $links = $response->{'query'}->{'pages'}->{"$pageid"}->links;

        $items = array();

        foreach ($links as $link) {
            $content = html_writer::span('', 'glyphicon glyphicon-chevron-right');
            $content .= $link->title;

            $items[] = html_writer::link('', $content, array(
                        'data-title' => $link->title,
                        'class' => 'item',
                        'data-target' => '.help.doc.sidebar'
            ));
        }

        return $items;
    }

    /**
     * Returns html string
     * @return string
     */
    public function render() {

        $content = array_reduce($this->get_topics(), function($carry, $item) {
                    $carry .= $item;
                    return $carry;
        }, '');

        $topic = ucfirst($this->keyword);
        $title = $this->title($topic . ' help topics');
        $list = html_writer::div($content, 'ui topics list');
        return $title . html_writer::div($list, 'ui stacked segment');
    }

}
