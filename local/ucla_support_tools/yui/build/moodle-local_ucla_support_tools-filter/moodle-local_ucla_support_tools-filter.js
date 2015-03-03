YUI.add('moodle-local_ucla_support_tools-filter', function (Y, NAME) {

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


M.local_ucla_support_tools = M.local_ucla_support_tools || {};

M.local_ucla_support_tools.filter = {
    /**
     * Creates a tool filter.
     * 
     *      config: {
     *          input_node_id: "#id",
     *          target_nodes: ".css .target",
     *          filter_nodes: ".css .target"
     *      }
     * 
     * @param {type} config
     * @returns {undefined}
     */
    tools: function (config) {
        
        var tool_filter = Y.Base.create('pieFilter', Y.Base, [Y.AutoCompleteBase], {
            initializer: function () {
                this._bindUIACBase();
                this._syncUIACBase();
            }
        }),
        filter = new tool_filter({
            inputNode: config.input_node_id,
            minQueryLength: 0,
            queryDelay: 0,
            // Run an immediately-invoked function that returns an array of results to
            // be used for each query, based on the photos on the page. Since the list
            // of photos remains static, this saves time by not gathering the results
            // for each query.
            //
            // If the list of results were not static, we could simply set the source
            // to the function itself rather than invoking the function immediately,
            // and it would then run on every query.
            source: (function () {
                var results = [];

                // Build an array of results containing each photo in the list.
                Y.all(config.target_nodes).each(function (node) {
                    results.push({
                        node: node,
                        tags: node.getAttribute('data-keywords')
                    });
                });

                return results;
            }), // <-- Note the parens. This invokes the function immediately.
            //     Remove these to invoke the function on every query instead.

            // Specify that the "tags" property of each result object contains the text
            // to filter on.
            resultTextLocator: 'tags',
            // Use a result filter to filter the photo results based on their tags.
            resultFilters: 'phraseMatch'
        });

        // Subscribe to the "results" event and update photo visibility based on
        // whether or not they were included in the list of results.
        filter.on('results', function (e) {
            // First hide all the photos.
            Y.all(config.filter_nodes).addClass('hidden');

            // Then unhide the ones that are in the current result list.
            Y.Array.each(e.results, function (result) {
                result.raw.node.ancestor('li').removeClass('hidden');
            });
        });
    },
    /**
     * Adds filtering capability to the category labels.
     */
    categories: function () {

        Y.one('.ucla-support-tool-category-labels').delegate('change', function (e) {

            var checkbox = e.target;
            var category_node = Y.one('.ucla-support-category[data-id="' + checkbox.getData('id') + '"]');

            if (checkbox.get('checked')) {
                category_node.ancestor('li').removeClass('collapsed');
                checkbox.ancestor('.category-label').removeClass('selected');
                checkbox.previous('label').one('i').replaceClass('fa-plus-square', 'fa-minus-square');
            } else {
                category_node.ancestor('li').addClass('collapsed');
                checkbox.ancestor('.category-label').addClass('selected');
                checkbox.previous('label').one('i').replaceClass('fa-minus-square', 'fa-plus-square');
            }

        }, 'input[type="checkbox"]');
        
        Y.one('.ucla-support-tool-alltools').delegate('click', function (e) {
            e.preventDefault();

            // Hide all.
            Y.all('.category-label input[type="checkbox"]').each(function (node) {
                if (node.get('checked')) {
                    node.previous('label').simulate('click');
                }
            });
            // Show selected.
            var catid = e.target.getData('catid');
            Y.one('.category-label input[data-id="' + catid + '"]').set('checked', 'false').simulate('change');

        }, 'a[data-action="filtercategory"]');
    }
};


}, '@VERSION@', {"requires": ["autocomplete-base", "autocomplete-filters", "node-event-simulate"]});
