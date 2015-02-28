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

        $catheader = html_writer::tag('h4', $category->name, array('class' => 'cat-title'));
        $catcolor = html_writer::div('', 'cat-color', array('style' => 'background-color: #' . $category->color));
        $ul = html_writer::tag('ul', implode("\n", $content));
        $catbody = html_writer::div($ul, 'ucla-support-category-body ucla-support-tool-grid');
        
        return html_writer::div($catcolor . $catheader . $catbody, 'ucla-support-category', array('data-id' => $category->get_id(), 'data-keyword' => addslashes($category->name)));
    }
    
    /**
     * Renders a tool card.
     * 
     * @param \local_ucla_support_tools_tool $tool
     * @return string renderable HTML
     */
    function render_local_ucla_support_tools_tool(\local_ucla_support_tools_tool $tool) {

        $out = array();

        // Set favorite state.
        $starstate = 'fa-star-o';
        $startitle = 'Add to favorites';
        if ($tool->is_favorite())  {
            $starstate = 'fa-star';
            $startitle = 'Remove from favorites';
        }
        $star = html_writer::link('', html_writer::tag('i', '', array('class' => "fa fa-lg $starstate")), array('data-action' => 'favorite', 'data-id' => $tool->get_id(), 'title' => $startitle));

        $namelink = html_writer::link(new moodle_url($tool->url), $tool->name, array('title' => $tool->url, 'class' => 'tool-link'));
        $out[] = html_writer::tag('h5', $star. $namelink, array('class' => 'title'));

        // Conditionally show the description.
        if (!empty($tool->description)) {
            $out[] = html_writer::div($tool->description, 'body');
        }

        if (!empty($tool->docs_url)) {
            $out[] = html_writer::link(new moodle_url($tool->docs_url), 'Docs' . html_writer::tag('i', '', array('class' => 'fa fa-question')), array('title' => $tool->docs_url, 'class' => 'ucla-support-tool-link-docs'));
        }

        $cats = $tool->get_categories();
        if (!empty($cats)) {
            $catout = array();
            foreach ($cats as $cat) {
                $catout[] = html_writer::link('', '', array('class' => 'cat-color', 'style' => 'background-color:#' .$cat->color, 'title' => 'In category: ' . $cat->name, 'data-catid' => $cat->get_id(), 'data-action' => 'filtercategory'));
            }
            $out[] = html_writer::div(implode("\n", $catout), 'tool-cats');
        }

        // Tool editing.
        if (has_capability('local/ucla_support_tools:edit', context_system::instance())) {
            $delete = html_writer::link('', html_writer::tag('i', '', array('class' => 'fa fa-times-circle fa-lg')) . 'Delete', array('data-action' => 'delete', 'data-id' => $tool->get_id(), 'title' => 'Delete tool'));
            $remove = html_writer::link('', html_writer::tag('i', '', array('class' => 'fa fa-minus-circle fa-lg')) . 'Remove', array('data-action' => 'remove', 'data-id' => $tool->get_id(), 'title' => 'Remove tool from category'));
            $edit = html_writer::link('', html_writer::tag('i', '', array('class' => 'fa fa-pencil-square fa-lg')) . 'Edit', array('data-action' => 'edit', 'data-id' => $tool->get_id(), 'title' => 'Edit tool information'));

            $out[] = html_writer::div($edit.$remove.$delete, 'edit');
        }
        return html_writer::div(implode("\n", $out), 'ucla-support-tool', array('data-id' => $tool->get_id(), 'data-keywords' => strtolower(addslashes($tool->name . ' ' . $tool->description))));
    }
    
    /**
     * Renders a tool edit form.
     * 
     * @param \local_ucla_support_tools_tool $tool
     * @return string HTML
     */
    function render_tool_edit(\local_ucla_support_tools_tool $tool) {
        $out = array();

        $out[] = html_writer::empty_tag('input', array('type' => 'hidden', 'value' => $tool->get_id(), 'id' => 'toolid-output'));

        $label = html_writer::tag('label', 'Name', array('for' => 'toolname-output'));
        $input = html_writer::empty_tag('input', array('id' => 'toolname-output', 'class' => 'form-control', 'value' => $tool->name, 'placeholder' => 'Enter a name for this tool', 'type' => 'text'));
        $out[] = html_writer::div($label . $input, 'form-group');

        $label = html_writer::tag('label', 'Link', array('for' => 'toolurl-output'));
        $input = html_writer::empty_tag('input', array('id' => 'toolurl-output', 'class' => 'form-control', 'value' => $tool->url, 'placeholder' => 'Link for this tool', 'type' => 'text'));
        $out[] = html_writer::div($label . $input, 'form-group');

        $label = html_writer::tag('label', 'Documentation link', array('for' => 'tooldocs-output'));
        $input = html_writer::empty_tag('input', array('id' => 'tooldocs-output', 'class' => 'form-control', 'value' => $tool->docs_url, 'placeholder' => 'Documentation link?', 'type' => 'text'));
        $out[] = html_writer::div($label . $input, 'form-group');

        $label = html_writer::tag('label', 'Description', array('for' => 'tooldesc-output'));
        $input = html_writer::tag('textarea', $tool->description, array('id' => 'tooldesc-output', 'class' => 'form-control', 'rows' => '5', 'placeholder' => 'Description is searchable'));
        $out[] = html_writer::div($label . $input, 'form-group');

        return implode("\n", $out);
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
        $button = html_writer::div('Create a new category', 'btn btn-primary ucla-support-tool-category-button-add');
        $box = html_writer::div($button, 'ucla-support-tool-category-add');
        return $box;
    }
    
    /**
     * Renders the 'Tool add' button.
     * 
     * @return string HTML
     */
    function tool_create_button() {
        $button = html_writer::div(' Create a new tool', 'btn btn-primary ucla-support-tool-button-add');
        $box = html_writer::div($button, 'ucla-support-tool-add');
        return $box;
    }

    /**
     * Returns the HTML to display the tool export feature.
     *
     * @return string
     */
    public function tool_export_button() {
        $url = new moodle_url('/local/ucla_support_tools/export.php');
        $icon = html_writer::tag('i', '', array('class' => 'fa fa-download'));
        $button = html_writer::div($icon . ' Export tools', 'btn btn-primary');
        $box = html_writer::link($url, $button, array('class' => 'ucla-support-tool-export'));
        return $box;
    }

    /**
     * Returns the HTML to display the tool import feature.
     *
     * @return string
     */
    public function tool_import_button() {
        $url = new moodle_url('/local/ucla_support_tools/import.php');
        $icon = html_writer::tag('i', '', array('class' => 'fa fa-upload'));
        $button = html_writer::div($icon . ' Import tools', 'btn btn-primary');
        $box = html_writer::link($url, $button, array('class' => 'ucla-support-tool-import'));
        return $box;
    }
    
    /**
     * Renders all tools in an unordered list.
     * 
     * @return string renderable HTML
     */
    function tools() {
        $tools = \local_ucla_support_tools_tool::fetch_all();
        $out = array();
        
        foreach ($tools as $tool) {
            $out[] = html_writer::tag('li', $this->render($tool));
        }
        
        // Add #grid to this so that 'salvattore' can identify it.
        return html_writer::tag('ul', implode("\n", $out), array('id' => 'grid', 'data-columns' => '4'));
    }

    /**
     * Generates and input field to filter search all tools.
     * 
     * @return string renderable HTML
     */
    function all_tools_filter() {
        $out[] = html_writer::empty_tag('input', array('id' => 'ucla-support-filter-input', 'type' => 'text', 'class' => 'form-control input-lg', 'placeholder' => 'Search all tools'));
        return html_writer::div(implode("\n", $out), 'form-group');
    }

    /**
     * Renders all categories in an unordered list.
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

    /**
     * Renders a category label.  These labels let you filter and edit categories.
     * 
     * @param \local_ucla_support_tools_category $cat
     * @return string HTML
     */
    function category_label(\local_ucla_support_tools_category $cat) {

        $input = html_writer::empty_tag('input', array('type' => 'checkbox', 'name' => 'category-label-' . $cat->get_id(), 'id' => 'category-label-' . $cat->get_id(), 'data-id' => $cat->get_id(), 'checked' => 'true'));
        $icon = html_writer::tag('i', '', array('class' => 'fa fa-minus-square'));
        $label = html_writer::tag('label', $cat->name . $icon, array('for' => 'category-label-' . $cat->get_id()));

        $catcircle = html_writer::span('', 'cat-color', array('style' => 'background-color: #' . $cat->color));

        $out = '';
        if (has_capability('local/ucla_support_tools:edit', context_system::instance())) {
            // Button to delete category
            $delete = html_writer::link('', html_writer::tag('i', '', array('class' => 'fa fa-times-circle fa-lg')) . 'Delete',
                     array('data-action' => 'delete', 'data-id' => $cat->get_id(), 'title' => 'Delete category'));
            $edit = html_writer::link('', html_writer::tag('i', '', array('class' => 'fa fa-pencil-square fa-lg')) . 'Edit', array('data-action' => 'edit', 'data-id' => $cat->get_id(), 'title' => 'Edit category info'));

            $out = html_writer::div($edit. $delete, 'edit', array());
        }

        return html_writer::div($catcircle . $label . $input . $out, 'category-label', array('data-id' => $cat->get_id()));
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

    /**
     * Render favorites link for mysites.
     * 
     * @param \local_ucla_support_tools_tool $tool
     * @return string HTML
     */
    protected function render_favorite_mysites_tool(\local_ucla_support_tools_tool $tool) {

        $star = html_writer::span(html_writer::tag('i', '', array('class' => "fa fa-lg fa-star")));

        $namelink = html_writer::link(new moodle_url($tool->url), $tool->name, array('title' => $tool->name, 'class' => 'tool-link'));
        $out[] = html_writer::tag('h5', $star . $namelink , array('class' => 'title'));
        // Conditionally show the description.
        if (!empty($tool->description)) {
            $out[] = html_writer::div($tool->description, 'body');
        }
        return html_writer::div(implode("\n", $out), 'ucla-support-tool favorite-view', array('data-id' => $tool->get_id(), 'data-keywords' => strtolower(addslashes($tool->name . ' ' . $tool->description))));
    }

    /**
     * Renders a list of favorite tools to display in 'mysites'
     * @return string HTML
     */
    function mysites_favorites() {
        
        $title = html_writer::tag('h4', 'Support tools');
        $favs = \local_ucla_support_tools_tool::fetch_favorites();
        $cols = array();
        foreach ($favs as $fav) {
            $cols[] = html_writer::tag('li', $this->render_favorite_mysites_tool($fav));
        }
        $list = html_writer::tag('ul', implode("\n", $cols));
        return html_writer::div($title . $list, 'ucla-support-tools-mysites-favorites');
    }
}
