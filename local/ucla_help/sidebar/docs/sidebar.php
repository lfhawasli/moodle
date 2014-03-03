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


class sidebar_docs extends sidebar_html implements sidebar_widget {

    private $keywordquery = 'https://docs.ccle.ucla.edu/api.php?action=query&titles={title}&prop=links&format=json';

    public function __construct($keyword) {
        global $PAGE;

        $PAGE->requires->js('/theme/uclashared/package/sematic-ui/uncompressed/modules/accordion.js');
        $PAGE->requires->yui_module('moodle-local_ucla_help-doc_loader', 'M.local_ucla_help.init', 
                array(array('help' => '/local/ucla_help/help/gradebook')));

        $this->keyword = $keyword;
    }

    private function curl($url) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }

    private function get_topics() {
        $url = preg_replace('/{title}/', $this->keyword, $this->keywordquery);
        $response = $this->curl($url);

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
