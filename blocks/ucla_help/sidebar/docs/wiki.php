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
 * Class defining the sidebar wiki renderer
 *
 * @package    block_ucla_help
 * @author     Rex Lorenzo <rex@seas.ucla.edu>
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../../config.php');
require_once(dirname(__FILE__) . '/../../../../mod/wiki/parser/parser.php');
require_once(dirname(__FILE__) . '/../../../../mod/wiki/parser/markups/nwiki.php');

/**
 * Special wiki renderer for help sidebar.
 *
 * Uses Moodle's built-in wiki parser and extends
 * it to render items with an accordion UI.
 *
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sidebar_wiki_parser extends nwiki_parser {

    /**
     * Template for url query
     * @var string $urlquery
     */
    private $urlquery = '?action=query&titles={title}&prop=imageinfo&iiprop=url&format=json';
    /**
     * Template for section query
     * @var string $sectionquery
     */
    private $sectionquery = '?action=parse&page={title}&prop=sections&format=json';
    /**
     * Template for page section query
     * @var string $pagesectionquery
     */
    private $pagesectionquery = '?action=query&titles={title}&prop=revisions&rvprop=content&format=json&rvsection={section}';

    /**
     * Rules for  the block
     * @var array $blockrules
     */
    protected $blockrules = array(
        'nowiki' => array(
            'expression' => "/^<nowiki>(.*?)<\/nowiki>/ims",
            'tags' => array(),
            'token' => array('<nowiki>', '</nowiki>')
        ),
        'header' => array(
            'expression' => "/^\ *(={1,6})\ *(.+?)(={1,6})\ *$/ims",
            'tags' => array(), // None.
            'token' => '='
        ),
        'line_break' => array(
            'expression' => "/^-{3,4}\s*$/im",
            'tags' => array(),
            'token' => '---'
        ),
        'desc_list' => array(
            'expression' => "/(?:^.+?\:.+?\;\n)+/ims",
            'tags' => array(),
            'token' => array(':', ';'),
            'tag' => 'dl'
        ),
        'table' => array(
            'expression' => "/\{\|(.+?)\|\}/ims"
        ),
        'tab_paragraph' => array(
            'expression' => "/^(\:+)(.+?)$/ims",
            'tag' => 'p'
        ),
        'list' => array(
            'expression' => "/^((?:\ *[\*|#]{1,5}\ *.+?)+)(\n\s*(?:\n|<(?:h\d|pre"
            . "|table|tbody|thead|tr|th|td|ul|li|ol|hr)\ *\/?>))/ims",
            'tags' => array(),
            'token' => array('*', '#')
        ),
        'paragraph' => array(
            'expression' => "/^\ *((?:<(?!\ *\/?(?:h\d|pre|table|tbody|thead|tr|th|td|ul|li|ol|hr)\ *\/?>)|[^<\s]).+?)\n\s*\n/ims",
        // Remove the <p> tag from rendering at all...
        )
    );

    /**
     * General wiki query.
     *
     * @param string $url
     * @return json_obj
     */
    private function query($url) {
        $apiurl = get_config('block_ucla_help', 'docs_wiki_api');
        $url = $apiurl . $url;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5'
        ));
        $exec = curl_exec($curl);

        $response = json_decode($exec);
        curl_close($curl);

        return $response;
    }

    /**
     * Get total number of sections in page.
     *
     * @param string $keyword
     * @return int
     */
    private function get_num_sections($keyword) {
        $url = preg_replace('/{title}/', urlencode($keyword), $this->sectionquery);
        $response = $this->query($url);
        return count($response->{'parse'}->{'sections'});
    }

    /**
     * Get content within given section.
     *
     * @param string $keyword
     * @param int $section
     * @return string
     */
    private function get_section_content($keyword, $section) {
        $url = preg_replace('/{title}/', urlencode($keyword), $this->pagesectionquery);
        $url = preg_replace('/{section}/', urlencode($section), $url);

        $response = $this->query($url);

        $keys = array_keys((array) $response->{'query'}->{'pages'});
        $pageid = array_pop($keys);
        $text = $response->{'query'}->{'pages'}->{"$pageid"}->revisions[0]->{'*'};

        return $text;
    }

    /**
     * Get source URL of given image.
     *
     * @param string $image
     * @return string $url
     */
    private function get_image_src($image) {
        $url = preg_replace('/{title}/', $image, $this->urlquery);

        $response = $this->query($url);

        $keys = array_keys((array) $response->{'query'}->{'pages'});
        $pageid = array_pop($keys);

        $src = $response->{'query'}->{'pages'}->{"$pageid"}->imageinfo[0]->{'url'};

        return $src;
    }

    /**
     * Parses a sidebar wiki entry by keyword.
     *
     * @param string $page
     * @return array
     */
    public function parse_doc_page($page) {

        // Get total number of sections for page.
        $sections = $this->get_num_sections($page);

        $toparse = array();
        $parsed = array();

        // First get section 0...
        $sectionzero = $this->get_section_content($page, 0);
        $parsedsectionzero = $this->parse($sectionzero);

        if (!empty($parsedsectionzero['parsed_text'])) {
            $parsed[] = array(
                'parsed_text' => html_writer::div($parsedsectionzero['parsed_text'], 'doc intro')
            );
        }

        // Now get remaining sections...
        for ($i = 1; $i <= $sections; $i++) {
            $toparse[] = $this->get_section_content($page, $i);
        }

        // And parse them.
        foreach ($toparse as $parse) {
            $parsed[] = $this->parse($parse);
        }

        return $parsed;
    }

    /**
     * Parse a special header for sidebar.
     *
     * @param string $text
     * @param int $level
     * @return string
     */
    protected function generate_header($text, $level) {
        $text = trim($text);

        if (!$this->pretty_print && $level == 1) {
            $text .= parser_utils::h('a', '[' . get_string('editsection', 'wiki') . ']',
                    array('href' => "edit.php?pageid={$this->wiki_page_id}&section=" .
                        urlencode($text), 'class' => 'wiki_edit_section'));
        }

        // Target <h2> tags.
        if ($level == 2) {
            return parser_utils::h('div', $text, array('class' => 'title')) . "\n\n";
        }
        return parser_utils::h('h' . $level, $text) . "\n\n";
    }

    /**
     * Ignore TOC processing.
     *
     * @return null
     */
    protected function process_toc() {
        return;
    }

    /**
     * Wrap lists in a "content" container.
     *
     * @param string $match
     * @return string
     */
    protected function list_block_rule($match) {
        $list = parent::list_block_rule($match);

        return parser_utils::h('div', $list, array('class' => 'content')) . "\n\n";
    }

    /**
     * Parse images and links.  Images are rendered and links open in a new page.
     *
     * @param string $text
     * @return string
     */
    protected function format_link($text) {

        $docsurl = get_config('block_ucla_help', 'docs_wiki_url');

        // Render images inline.
        if (preg_match('/.*\.jpg|.*\.jpeg/', $text)) {

            $img = explode('|', $text);
            $file = str_replace(' ', '', $img[0]);
            $src = $this->get_image_src($file);
            $image = html_writer::tag('img', '', array('src' => $src));
            $url = html_writer::link($docsurl . '/index.php?title=' . str_replace(' ', '_', $text), $image, array('target' => '_blank')
            );
            return parser_utils::h('div', $url, array('class' => 'sidebar-image'));
        } else if (preg_match('/^Category:/', $text)) {
            // Render categories as tags.
            return parser_utils::h('div', $text, array('class' => 'label-bstp label-success'));
        }

        // Render links.
        $links = explode('|', $text);

        $out = '';
        foreach ($links as $link) {
            $newwindow = html_writer::span('', 'glyphicon glyphicon-new-window');
            $out .= parser_utils::h('a', $link . $newwindow, array(
                        'href' => $docsurl . '/index.php?title=' . str_replace(' ', '_', trim($link)),
                        'target' => '_blank',
                        'class' => 'external'
                            )
            );
            $out .= ' ';
        }

        return $out;
    }

    /**
     * Post cleanup to remove __toc__ text variations.
     */
    protected function after_parsing() {
        // Remove the table of contents marker.
        $this->string = preg_replace("/__[tToOcC]{3}__/", "", $this->string);

        parent::after_parsing();
    }

}
