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
 * The control panel section, a collection of several tools.
 *
 * @package block_ucla_control_panel
 * @copyright  UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

defined('MOODLE_INTERNAL') || die();
/**
 * Renderer for ucla control panel
 * @copyright  UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class ucla_cp_renderer {

    /**
     * @var array $history
     */
    private $history = array();

    /**
     * Comparator function for sorting based on key
     *
     * @param object $a
     * @param object $b
     * @return int
     */
    public static function cmp($a, $b) {
        return( strcmp($a->get_key(), $b->get_key()) );
    }

    /**
     * get_content_array()
     * @param array $contents data to sort into tables
     * @param int $size the size of the tables you want the data sorted into.
     * @param bool $sort whether or not you want to items sorted based on name.
     *
     * @return Array This will return the data sorted into tables.
     *      Normally, this table will be 2 levels deep (Array => Array).
     *      Each key should be the identifier within the lang file
     *      that uses a language convention.
     *
     *      <item>_pre represents strings that are printed before the link.
     *      <item>_post represents the string that is printed after the link.
     **/
    public static function get_content_array($contents, $size=null, $sort=true) {
        $allstuff = array();

        // This is the number of groups to sort this into.
        if ($size === null) {
            $size = floor(count($contents) / 2) + 1;

            if ($size == 0) {
                $size = 1;
            }
        }

        foreach ($contents as $content) {
            $action = $content;
            $title = $content->get_key();

            $allstuff[] = $action;
        }

        usort($allstuff, "ucla_cp_renderer::cmp");

        $dispstuff = array();

        $dispcat = array();
        foreach ($allstuff as $title => $action) {
            if (count($dispcat) == $size) {
                $dispstuff[] = $dispcat;
                $dispcat = array();
            }

            $dispcat[$title] = $action;
        }

        if (!empty($dispcat)) {
            $dispstuff[] = $dispcat;
        }

        return $dispstuff;
    }

    /**
     * Builds the string with the string and the descriptions, pre and post.
     *
     * @param ucla_cp_module $itemobj - This is the identifier for the
     *      current control panel item.
     * @param array $linkattributes - Attributes associated with the object if the
     * object is a link.
     * @return string The DOMs of the control panel description and link.
     **/
    public static function general_descriptive_link($itemobj, $linkattributes = null) {
        global $OUTPUT;
        $fitem = '';

        $bucp = $itemobj->associated_block();

        $item = $itemobj->itemname;
        $link = $itemobj->get_action();

        if ($itemobj->get_opt('pre')) {
            $fitem .= html_writer::tag('span', get_string($item . '_pre',
                $bucp, $itemobj), array('class' => 'pre-link'));
        }

        // If the object is plain text, just include the object name in the string.
        if (get_class($itemobj) == 'ucla_cp_text_module') {
            $fitem .= $item;
        } else if ($link === null) {
            // If the object is a tag.
            $fitem .= html_writer::tag('span', get_string($item, $bucp,
                $itemobj), array('class' => 'disabled'));
        } else {
            $fitem .= html_writer::link($link, get_string($item, $bucp,
                $itemobj), $linkattributes);
        }

        // One needs to explicitly hide the post description.
        if ($itemobj->get_opt('post') !== false) {
            $fitem .= html_writer::tag('span', get_string($item . '_post',
                    $bucp, $itemobj), array('class' => 'post-link'));

            // If enabled, display an option to toggle availability of this option.
            if ($itemobj->get_opt('toggle')) {
                $msg = get_string($item . '_toggle', $bucp, $itemobj);

                // Checks if $item ends in "disabled", in which case we want the toggle to enable it (and vice versa).
                $enable = (stripos(strrev($item), strrev('_disabled')) === 0);
                // Set the action to toggle the current availability.
                $action = 'toggle_' . $item;

                $icon = ($enable) ? 'hide' : 'show';
                $iconurl = $OUTPUT->pix_url('t/' . $icon);
                $fitem .= html_writer::link(
                        new moodle_url('', array('action' => 'toggle_' . $item,
                        'course_id' => required_param('course_id', PARAM_INT))),
                        html_writer::img($iconurl, '',
                        array('height' => 10, 'class' => 'toggle-link-icon'))
                        . $msg, array('class' => 'toggle-link'));
            }
        }

        return $fitem;
    }

    /**
     * Adds an icon to the link and description.
     *
     * @see ucla_cp_renderer::general_descriptive_link()
     *
     * @param ucla_cp_modules $itemobj - The item to display.
     * @return string The DOMs of the control panel, with an image
     *      and whatever is returned by general_descriptive_link
     **/
    public static function general_icon_link($itemobj) {
        global $OUTPUT;

        $bucp = $itemobj->associated_block();

        $item = $itemobj->itemname;
        $itemstring = get_string($item, $bucp, $itemobj);

        $fitem = '';
        // BEGIN UCLA MOD: CCLE-2869-Add Empty Alt attribute for icons on Control Panel.
        $fitem .= html_writer::start_tag('a',
            array('href' => $itemobj->get_action()));

        $fitem .= html_writer::start_tag('img',
                array('src' => $OUTPUT->pix_url('cp_' . $item, $bucp),
                      'alt' => $itemstring, 'class' => 'general_icon'));
        $fitem .= html_writer::end_tag('a');

        $fitem .= html_writer::start_tag('span', array('class' => 'general_icon_text'));
        $fitem .= self::general_descriptive_link($itemobj);

        $fitem .= html_writer::end_tag('span');

        // END UCLA MOD: CCLE-2869.
        return $fitem;
    }

    /**
     * This function will take the contents of a 2-layer deep
     * array and generate the string that contains the contents
     * in a div-split table. It can also generate the contents.
     *
     * @param array $contents - The contents to diplay using the renderer.
     * @param boolean $format - If this is true, then we will send the data
     *      through {@link get_content_array}.
     * @param string $orient - Which orientation handler to use to render the
     *      display. Currently accepts two options (defaults to rows) if the
     *      option does not exist.
     *
     *      'col': This means that we expect an array containing 2 arrays of
     *          the elements we wish to render.
     *
     *      'row': This means taht we expect an array containing arrays each
     *          with 2 of the elements we wish to render.
     *
     * @param string $handler - This is the callback function used to display
     *      each element. Defaults to general_descriptive_link, and will crash
     *      the script if you provide a non-existant function.
     **/
    public static function control_panel_contents($contents, $format=false,
            $orient='col', $handler='general_descriptive_link') {

        if ($format) {
            $contents = self::get_content_array($contents, 2);
        }

        $fulltable = '';

        $columns = ($orient == 'col');

        foreach ($contents as $contentrow) {

            $rowcontents = '';

            // This corresponds to bootstrap grid.
            $responsiveclass = 'col-sm-6 col-xs-12 item';

            foreach ($contentrow as $contentitem => $contentlink) {
                $theoutput = html_writer::start_tag('div',
                    array('class' => $responsiveclass . ' ' . $contentlink->itemname));

                $theoutput .= self::$handler(
                    $contentlink);

                $theoutput .= html_writer::end_tag('div');
                $rowcontents .= $theoutput;
            }

            $fulltable .= html_writer::tag('div', $rowcontents,
                array('class' => 'row'));
        }

        return $fulltable;
    }
}
