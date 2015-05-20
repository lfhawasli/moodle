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
     * Creates a tool filter
     *
     *      config: {
     *          input_node_id: "#id",
     *          target_nodes: ".css .target",
     *          filter_nodes: ".css .target",
     *          category_nodes: "#id .target"
     *      }
     *
     * @param {type} config
     * @returns {undefined}
     */
    tools: function (config) {
        Y.log('Loading tool search filter', 'info', 'local_ucla_support_tools');

        // Keep track of which category nodes were suppressed from filter
        var grid = Y.one('#cat-grid').getDOMNode();
        var numRows;
        var allCategoryNodes = [];

        var ToolFilter = Y.Base.create('pieFilter', Y.Base, [Y.AutoCompleteBase], {
            initializer: function () {
                this._bindUIACBase();
                this._syncUIACBase();
            }
        }),
        filter = new ToolFilter({
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

                if (allCategoryNodes.length) {
                    // Refresh the grid.
                    Y.all(config.category_nodes).each(function (categoryNode) {
                        categoryNode.ancestor('li').remove(false);
                    });
                    Y.all(allCategoryNodes).each(function (categoryNode) {
                        var wrapperNode = Y.Node.create('<li/>').append(categoryNode);
                        salvattore.append_elements(grid, [wrapperNode.getDOMNode()]);
                    });
                    numRows = Math.ceil(allCategoryNodes.length / Y.one('#cat-grid').getData('columns'));
                    allCategoryNodes = [];
                } else {
                    // Initial numRows value before allCategoryNodes array is populated.
                    numRows = Y.one('.column').get('children').size();
                }

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
            // Only need to filter if a query is non-empty.
            if (e.query) {
                // First hide all the photos.
                Y.all(config.filter_nodes).addClass('hidden');

                // Then unhide the results.
                Y.Array.each(e.results, function (result) {
                    var resultNode = result.raw.node;
                    resultNode.ancestor('li').removeClass('hidden');
                });

                // For categories, we need to format our arrays to properly display
                // the order because the order for Y.all is top to bottom, left to right
                // while appending in salvattore is left to right, top to bottom.
                var displayedNodes = [];
                // Create array for each row.
                for (var i = 0; i < numRows; i++) {
                    allCategoryNodes.push([]);
                    displayedNodes.push([]);
                }
                // Pad the columns.
                Y.all('.column').each(function (column) {
                    if (column.get('children').size() < numRows) {
                        var fillerCategory = Y.Node.create('<li/>');
                        Y.Node.create('<div/>').addClass('ucla-support-category filler-category').appendTo(fillerCategory);
                        column.append(fillerCategory);
                    }
                });

                Y.all(config.category_nodes).each(function (categoryNode, categoryIndex) {
                    // Keep track of all the categories, for order.
                    var rowIndex = categoryIndex % numRows;
                    if (!categoryNode.hasClass('filler-category')) {
                        allCategoryNodes[rowIndex].push(categoryNode);
                    }
                    // Hide all categories.
                    categoryNode.ancestor('li').remove(false);
                    // Keep track of result categories.
                    Y.Array.each(e.results, function (result) {
                        var resultNode = result.raw.node;
                        var resultCategoryNode = resultNode.ancestor('.ucla-support-category');
                        if (resultCategoryNode && resultCategoryNode === categoryNode) {
                            displayedNodes[rowIndex].push(categoryNode);
                        }
                    });
                });

                allCategoryNodes = Y.Array.flatten(allCategoryNodes);
                displayedNodes = Y.Array.flatten(displayedNodes);
                displayedNodes = Y.Array.filter(displayedNodes, function(e) {
                    return !e.hasClass('filler-category');
                });
                displayedNodes = Y.Array.unique(displayedNodes);

                // Then add resulting categories back into the grid.
                Y.Array.each(displayedNodes, function (categoryNode) {
                    salvattore.append_elements(grid, [categoryNode.ancestor('li').getDOMNode()]);
                });
            } else {
                // Display all the photos
                Y.all(config.filter_nodes).removeClass('hidden');
            }
        });
    },
    /**
     * Adds filtering capability to the category labels.
     */
    categories: function () {
        Y.log('Loading category label filter', 'info', 'local_ucla_support_tools');

        Y.one('.ucla-support-tool-category-labels').delegate('change', function (e) {

            var checkbox = e.target;
            var category_node = Y.one('.ucla-support-category[data-id="' + checkbox.getData('id') + '"]');

            if (checkbox.get('checked')) {
                category_node.ancestor('li').removeClass('collapsed');
                checkbox.ancestor('.category-label').removeClass('selected');
                checkbox.previous('label').one('i').replaceClass('fa-toggle-off', 'fa-toggle-on');
            } else {
                category_node.ancestor('li').addClass('collapsed');
                checkbox.ancestor('.category-label').addClass('selected');
                checkbox.previous('label').one('i').replaceClass('fa-toggle-on', 'fa-toggle-off');
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
