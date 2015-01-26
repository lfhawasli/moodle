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
 * UCLA support tools plugin.
 *
 * @package    local_ucla_support_tools
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


class local_ucla_support_tools_renderer extends plugin_renderer_base {
    
    /**
     * Renders a category and all tools that belong to category.
     * 
     * @param \local_ucla_support_tools_category $category
     * @return string renderable HTML
     */
    function render_local_ucla_support_tools_category(\local_ucla_support_tools_category $category) {
        
        // Get all tools for this category.
        $tools = $category->get_tools();
             
        $content = array();

        foreach ($tools as $tool) {
            $content[] = html_writer::tag('li', $this->render($tool));
        }
        
        $delete = '';
        if (has_capability('local/ucla_support_tools:edit', context_system::instance())) {
            // Button to delete category
            $delete = html_writer::link('', html_writer::tag('i', '', array('class' => 'fa fa-times-circle fa-lg')),
                     array('data-action' => 'delete', 'data-id' => $category->get_id(), 'title' => 'Delete category'));
        }
                
        $catheader = html_writer::tag('h4', $category->name . $delete, array('class' => 'ucla-support-category-header', 'style' => 'border-left-color: #' . $category->color));
        $ul = html_writer::tag('ul', implode("\n", $content));
        $catbody = html_writer::div($ul, 'ucla-support-category-body');
        
        return html_writer::div($catheader . $catbody, 'ucla-support-category', array('data-id' => $category->get_id(), 'data-keyword' => addslashes($category->name)));
    }
    
    /**
     * Renders a tool card.
     * 
     * @param \local_ucla_support_tools_tool $tool
     * @return string renderable HTML
     */
    function render_local_ucla_support_tools_tool(\local_ucla_support_tools_tool $tool) {

        $out = array();
        
        $delete = '';
        $remove = '';

        if (has_capability('local/ucla_support_tools:edit', context_system::instance())) {
            $delete = html_writer::link('', html_writer::tag('i', '', array('class' => 'fa fa-times-circle fa-lg')), array('data-action' => 'delete', 'data-id' => $tool->get_id(), 'title' => 'Delete tool'));
            $remove = html_writer::link('', html_writer::tag('i', '', array('class' => 'fa fa-minus-circle fa-lg')), array('data-action' => 'remove', 'data-id' => $tool->get_id(), 'title' => 'Remove tool from category'));
        }

        $namelink = html_writer::link(new moodle_url($tool->url), $tool->name, array('title' => $tool->url, 'class' => 'ucla-support-tool-link'));

        $out[] = html_writer::tag('h5', $namelink . $remove . $delete, array('class' => 'ucla-support-tool-title'));
        $out[] = html_writer::div($tool->description, 'ucla-support-tool-body');
        
        if (!empty($tool->docs_url)) {
            $out[] = html_writer::link(new moodle_url($tool->docs_url), 'Docs' . html_writer::tag('i', '', array('class' => 'fa fa-question')), array('title' => $tool->docs_url, 'class' => 'ucla-support-tool-link-docs'));
        }

        return html_writer::div(implode("\n", $out), 'ucla-support-tool', array('data-id' => $tool->get_id(), 'data-keywords' => strtolower(addslashes($tool->name . ' ' . $tool->description))));
    }

    /**
     * Renders a tool tag.
     * 
     * @param \local_ucla_support_tools_tag $tag
     * @return string renderable HTML
     */
    function render_local_ucla_support_tools_tag(\local_ucla_support_tools_tag $tag) {
        $out = html_writer::div($tag->name, 'label-bstp ucla-support-label', array('style' => 'background-color:#' . $tag->color));
        return $out;
    }

    /**
     * Renders the 'Category add' button.
     * 
     * @return string renderable HTML
     */
    function category_create_button() {
//        $icon = html_writer::tag('i', '', array('class' => 'fa fa-plus-circle '));
        $button = html_writer::div('Create a new category', 'btn btn-primary ucla-support-tool-category-button-add');
        $box = html_writer::div($button, 'ucla-support-tool-category-add');
        return $box;
    }
    
    function tool_create_button() {
        $icon = html_writer::tag('i', '', array('class' => 'fa fa-plus-circle '));
        $button = html_writer::div($icon . ' Create a new tool', 'btn btn-primary ucla-support-tool-button-add');
        $box = html_writer::div($button, 'ucla-support-tool-add');
        return $box;
    }
    
    /**
     * Renders all tools.
     * 
     * @return string renderable HTML
     */
    function tools() {
        $tools = \local_ucla_support_tools_tool::fetch_all();
        $out = array();
        
        foreach ($tools as $tool) {
            $out[] = html_writer::tag('li', $this->render($tool));
        }
        
        return html_writer::tag('ul', implode("\n", $out));
    }
    
    /**
     * Generates an input field to search for tools.
     * 
     * @return string renderable HTML
     */
    function tool_filter() {
        $out = array();
//        $out[] = html_writer::tag('label', 'Find tools', array('class' => '', 'for' => 'ucla-support-tools-filter-input'));
        $out[] = html_writer::empty_tag('input', array('id' => 'ucla-support-tools-filter-input', 'type' => 'text', 'class' => 'form-control input-lg', 'placeholder' => 'Search all tools'));
        return html_writer::div(implode("\n", $out), 'form-group');
    }

    /**
     * Generates and input field to search for tools inside categories.
     * 
     * @return string renderable HTML
     */
    function category_tool_filter() {
//        $out[] = html_writer::tag('label', 'Find tools', array('class' => '', 'for' => 'ucla-support-category-filter-input'));
        $out[] = html_writer::empty_tag('input', array('id' => 'ucla-support-category-filter-input', 'type' => 'text', 'class' => 'form-control input-lg', 'placeholder' => 'Search all tools in categories'));
        return html_writer::div(implode("\n", $out), 'form-group');
    }

    /**
     * Renders all categories.
     * 
     * @return string renderable HTML
     */
    function categories() {

        $categories = \local_ucla_support_tools_category::fetch_all();
        $out = array();

        foreach ($categories as $cat) {
            $out[] = html_writer::tag('li', $this->render($cat));
        }

        return html_writer::tag('ul', implode("\n", $out));
    }
    
    function category_label(\local_ucla_support_tools_category $cat) {
        $input = html_writer::empty_tag('input', array('type' => 'checkbox', 'name' => 'category-label-' . $cat->get_id(), 'id' => 'category-label-' . $cat->get_id(), 'data-id' => $cat->get_id(), 'checked' => 'true'));
        $icon = html_writer::tag('i', '', array('class' => 'fa fa-chevron-right'));
        $label = html_writer::tag('label', $cat->name . $icon, array('for' => 'category-label-' . $cat->get_id()));

        return html_writer::div($label . $input, 'ucla-support-category-header', array('style' => 'border-left-color: #' . $cat->color, 'data-id' => $cat->get_id()));
    }

    /**
     * Prints category name as labels.
     * 
     * @return string renderable HTML
     */
    function category_labels() {

        $categories = \local_ucla_support_tools_category::fetch_all();

        $out = array();

        foreach ($categories as $cat) {
            $out[] = html_writer::tag('li', $this->category_label($cat));
        }

        $content = array();

        $content[] = html_writer::tag('ul', implode("\n", $out));

        if (has_capability('local/ucla_support_tools:edit', context_system::instance())) {
            $content[] = html_writer::div($this->category_create_button());
        }

        return html_writer::div(implode("\n", $content), 'ucla-support-tool-category-labels');
    }

    /**
     * Renders all tags.
     * 
     * @return string renderable HTML
     */
    function tags() {
        $tags = \local_ucla_support_tools_tag::fetch_all();

        $out = array();

        foreach ($tags as $tag) {
            $out[] = $this->render($tag);
        }

        return implode("\n", $out);
    }
}
