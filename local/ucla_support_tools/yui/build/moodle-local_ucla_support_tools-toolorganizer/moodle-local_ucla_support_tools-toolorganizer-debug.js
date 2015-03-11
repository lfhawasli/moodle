YUI.add('moodle-local_ucla_support_tools-toolorganizer', function (Y, NAME) {

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

M.local_ucla_support_tools.toolorganizer = {
    init: function () {
        Y.log('Loading tool organizer', 'info', 'local_ucla_support_tools');

        // Generate a dialog to create new tools.
        var tool_form = M.local_ucla_support_tools.dialog.form_with_inputs(new Y.ArrayList([
            {
                label: "Name",
                name: "toolname",
                placeholder: "Enter a name for this tool"
            },
            {
                label: "Link",
                name: "toolurl",
                placeholder: "Link for this tool"
            },
            {
                label: "Documentation link",
                name: "tooldocs",
                placeholder: "Documentation link?"
            }
        ]));

        var description = '<div class="form-group">' +
                '<label for="desc-output" >Description</label>' +
                '<textarea id="tooldesc-output" class="form-control" rows="5" placeholder="Description is searchable"></textarea>' +
                '</div>';
        tool_form.appendChild(Y.Node.create(description));

        // Set up event to load tool dialog.
        Y.one('.ucla-support-tool-button-add').on('click', function (e) {
            e.preventDefault();

            var tool_dialog = M.local_ucla_support_tools.dialog.create({
                id: "create-tool-dialog",
                title: "Create a new tool",
                body: tool_form.getHTML(),
                cancel: "Cancel",
                proceed: "Create tool"
            });
            tool_dialog.reset();
            tool_dialog.callback = M.local_ucla_support_tools.toolorganizer.create_tool;
            tool_dialog.validate = function () {
                return new Y.ArrayList([
                    Y.one('#toolname-output'),
                    Y.one('#tooldesc-output'),
                    Y.one('#toolurl-output')
                ]);
            };
            tool_dialog.show();
        }, this);

        // Set up event delegate for tool deletion.
        Y.one('.ucla-support-tool-alltools ul').delegate('click', function (e) {
            e.preventDefault();
            var id = e.currentTarget.getAttribute('data-id');
            this.delete_tool(id);

        }, 'a[data-action="delete"]', this);

        // Set up event delegate for tool edit.
        Y.one('.ucla-support-tool-alltools ul').delegate('click', function (e) {
            e.preventDefault();
            var id = e.currentTarget.getAttribute('data-id');
            this.edit_tool(id);

        }, 'a[data-action="edit"]', this);

        // Set up delegate for description toggle.
        Y.one('.ucla-support-tool-alltools ul').delegate('click', function (e) {
            e.preventDefault();
            e.currentTarget.ancestor('.ucla-support-tool').toggleClass('expanded');

        }, 'a[data-action="description"]', this);
    },
    create_tag_input: function (node, tags) {

        node.plug(Y.Plugin.AutoComplete, {
            allowTrailingDelimiter: true,
            minQueryLength: 0,
            queryDelay: 0,
            queryDelimiter: ',',
            source: tags,
            resultHighlighter: 'startsWith',
            // Chain together a startsWith filter followed by a custom result filter
            // that only displays tags that haven't already been selected.
            resultFilters: ['startsWith', function (query, results) {
                    // Split the current input value into an array based on comma delimiters.
                    var selected = node.get('value').split(/\s*,\s*/);

                    // Convert the array into a hash for faster lookups.
                    selected = Y.Array.hash(selected);

                    // Filter out any results that are already selected, then return the
                    // array of filtered results.
                    return Y.Array.filter(results, function (result) {
                        return !selected.hasOwnProperty(result.text);
                    });
                }]
        });
        node.on('focus', function () {
            node.ac.sendRequest('');
        });

        node.ac.after('select', function () {
            // Send the query on the next tick to ensure that the input node's blur
            // handler doesn't hide the result list right after we show it.
            setTimeout(function () {
                node.ac.sendRequest('');
                node.ac.show();
            }, 1);
        });
    },
    /**
     * Creates a tool.  If successful, it will create a node in the 'all tools' list.
     * 
     * @returns {bool} validate 
     */
    create_tool: function () {
        Y.log('Creating new tool', 'info', 'local_ucla_support_tools');

        return M.local_ucla_support_tools.toolorganizer.update_tool('createtool', function (data) {
            var html = '<li>' + data.html + '</li>';
            Y.one('.ucla-support-tool-alltools ul').appendChild(html);
        });
    },
    /**
     * Deletes a tool with a given id.  If successful, will delete the nodes 
     * wherever they exist.
     * 
     * @param {int} id
     */
    delete_tool: function (id) {
        var dialog = M.local_ucla_support_tools.dialog.create({
            id: 'delete-tool-dialog',
            title: "Delete tool",
            body: "Are you sure you want to permanently delete this tool?",
            cancel: "Cancel",
            proceed: "Delete tool"
        });

        dialog.callback = function () {
            Y.log('Removing tool with ID: ' + id, 'info', 'local_ucla_support_tools');

            Y.io(M.cfg.wwwroot + '/local/ucla_support_tools/rest.php', {
                method: 'POST',
                data: {
                    action: 'deletetool',
                    json: JSON.stringify({id: id}),
                    sesskey: M.cfg.sesskey
                },
                on: {
                    success: function (id, result) {
                        var data = JSON.parse(result.responseText);

                        if (data.status) {
                            Y.all('.ucla-support-tool[data-id="' + data.id + '"]').each(function (node) {
                                node.ancestor('li').remove(true);
                            });
                        }
                    }
                }
            });

            return true;
        };
        dialog.show();
    },
    /**
     * Generates and 'edit tool' dialog.
     * 
     * @param {int} id of tool
     */
    edit_tool: function (id) {

        Y.io(M.cfg.wwwroot + '/local/ucla_support_tools/rest.php', {
            method: 'GET',
            data: {
                action: 'gettooledit',
                json: JSON.stringify({id: id}),
                sesskey: M.cfg.sesskey
            },
            on: {
                success: function (id, result) {
                    var data = JSON.parse(result.responseText);

                    if (data.status) {
                        // Generate a dialog from data we get back.
                        var dialog = M.local_ucla_support_tools.dialog.create({
                            id: 'edit-tool-dialog',
                            title: "Edit tool",
                            body: data.html,
                            cancel: "Cancel",
                            proceed: "Save changes"
                        });

                        dialog.callback = M.local_ucla_support_tools.toolorganizer.edit_tool_callback;
                        dialog.show();
                    }
                }
            }
        });
    },
    /**
     * Panel callback for tool edit 
     * 
     * @return {bool} validation
     */
    edit_tool_callback: function () {
        var id = Y.one('#toolid-output').get('value');

        return M.local_ucla_support_tools.toolorganizer.update_tool('updatetool', function (data) {

            var newnode = Y.Node.create(data.html);

            Y.all('.ucla-support-tool[data-id="' + data.id + '"]').each(function (node) {
                node.set('innerHTML', newnode.getHTML());
            });
        }, {id: id});

    },
    /**
     * Sets up an ajax call to update tool data.  
     * 
     * @param {string} action
     * @param {function} callback function for successful callback.
     * @param {obj} opts optional data params to send via Y.io
     * 
     * @returns {bool} validation
     */
    update_tool: function (action, callback, opts) {

        var name = Y.one('#toolname-output').get('value');
        var desc = Y.one('#tooldesc-output').get('value');
        var url = Y.one('#toolurl-output').get('value');
        var docsurl = Y.one('#tooldocs-output').get('value');

        var alert = Y.one('#create-tool-dialog .alert');

        if (alert) {
            alert.remove(true);
        }

        var data = {
            name: name,
            desc: desc,
            url: url,
            docsurl: docsurl
        };

        if (opts) {
            data = Y.merge(data, opts);
        }

        var validate = false;

        Y.io(M.cfg.wwwroot + '/local/ucla_support_tools/rest.php', {
            method: 'POST',
            data: {
                action: action,
                json: JSON.stringify(data),
                sesskey: M.cfg.sesskey
            },
            on: {
                success: function (id, result) {
                    //
                    var data = JSON.parse(result.responseText);

                    if (data.status) {
                        // Forward result to specified callback.
                        callback(data);

                    } else if (data.error) {
                        // Check for invalid URL.
                        if (data.error.msg.indexOf('out_as_local_url') !== -1) {
                            var form = Y.one('#toolurl-output').ancestor('.form-group');
                            form.addClass('has-error');
                            form.one('label').setHTML('URL needs to be from this server');
                        }

                        if (data.error.msg.indexOf('Error writing to database') !== -1) {
                            Y.one('#create-tool-dialog h3.title').insert('<div class="alert alert-warning" role="alert"><strong>Unable to create tool!</strong> Perhaps you are reusing the name or the link?</div>', 'after');
                        }
                    }

                    validate = data.status;
                },
                failure: function (id, result) {
                    return false;
                }
            },
            // Hold execution until we validate.
            sync: true
        });

        return validate;
    }
};

}, '@VERSION@', {
    "requires": [
        "base",
        "node",
        "panel",
        "io",
        "autocomplete",
        "autocomplete-filters",
        "autocomplete-highlighters",
        "moodle-local_ucla_support_tools-filter"
    ]
});
