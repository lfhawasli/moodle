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

M.local_ucla_support_tools.categoryorganizer = {
    
    init: function () {
        Y.log('Loading category organizer', 'info', 'local_ucla_support_tools');
        
        M.local_ucla_support_tools.dragdrop.init();
        M.local_ucla_support_tools.filter.categories();
        
        // Start the category tool filter
        M.local_ucla_support_tools.filter.tools({
            input_node_id: "#ucla-support-category-filter-input",
            target_nodes: ".ucla-support-tool-category-list .ucla-support-tool",
            filter_nodes: ".ucla-support-tool-category-list .ucla-support-category li"
        });
        
        var category_panel_form = M.local_ucla_support_tools.dialog.form_with_inputs(new Y.ArrayList([
            {
                label: "Name for category",
                name: "catname",
                placeholder: "Enter a category name"
            },
            {
                label: "Color",
                name: "cathex",
                placeholder: ""
            }
        ]));
        
        // Attach the color picker node.
        var picker =  '<div class="picker well">' + 
                        '<div id="hue-dial"></div>' + 
                        '<div class="sliders">' +
                            '<div id="sat-slider"><strong>Saturation: <span></span></strong></div>' +
                            '<div id="lum-slider"><strong>Luminance: <span></span></strong></div>' +
                        '</div>' +
                        '<div class="color"></div>' +
                    '</div>';

        category_panel_form.appendChild(Y.Node.create(picker));
        
        var category_panel = M.local_ucla_support_tools.dialog.create({
            id: "create-category-dialog",
            title: "Create a new category",
            body: category_panel_form.getHTML(),
            cancel: "Cancel",
            proceed: "Create category"
        });

        // 'add category' button event.
        Y.one('.ucla-support-tool-category-button-add').on('click', function(){
            this.init_color_slider();
            
            category_panel.callback = M.local_ucla_support_tools.categoryorganizer.create_category;
            category_panel.validate = function() {
                return new Y.ArrayList([
                    Y.one('#catname-output'),
                ]);
            };

            category_panel.show();

        }, this);

        // Category deletion delegate event.
        Y.one('.ucla-support-tool-category-list ul').delegate('click', function (e) {
            e.preventDefault();
            var id = e.currentTarget.getData('id');
            this.delete_category(id);

        }, '.ucla-support-category-header a[data-action="delete"]', this);
        
        // Tool removal delegate event
        Y.one('.ucla-support-tool-category-list ul').delegate('click', function (e) {

            e.preventDefault();
            var toolid = e.currentTarget.getData('id');
            var catid = e.currentTarget.ancestor('.ucla-support-category').getData('id');
            
            this.remove_tool({
                toolid: toolid,
                catid: catid
            });

        }, '.ucla-support-tool-title a[data-action="remove"]', this);
    },
    init_color_slider: function(node) {
        
        var hue = new Y.Dial({
            min: 0,
            max: 360,
            stepsPerRevolution: 360,
            continuous: true,
            centerButtonDiameter: 0.4,
            render: '#hue-dial'
        }),
        sat = new Y.Slider({
            min: 0,
            max: 100,
            value: 100,
            render: '#sat-slider'
        }),
        lum = new Y.Slider({
            min: 0,
            max: 100,
            value: 50,
            render: '#lum-slider'
        }),
        satValue = Y.one('#sat-slider span'),
        lumValue = Y.one('#lum-slider span'),
        color = Y.one('.color');

        // 
        hue.after('valueChange', function(e) {
            updatePickerUI();
        });

        sat.after('thumbMove', function(e) {
            updatePickerUI();
        });

        lum.after('thumbMove', function(e) {
            lumValue.set('text', lum.get('value') + '%');
            updatePickerUI();
        });
        
        function setPickerUI(hsl) {
            if (typeof hsl.h !== 'undefined') {
                hue.set('value', +hsl.h);
            }

            if (typeof hsl.s !== 'undefined') {
                sat.set('value', +hsl.s);
            }

            if (typeof hsl.l !== 'undefined') {
                lum.set('value', +hsl.l);
            }
        }

        function updatePickerUI() {
            var h = hue.get('value'),
                s = sat.get('value'),
                l = lum.get('value'),
                hslString = Y.Color.fromArray([h, s, l], Y.Color.TYPES.HSL),
                hexString = Y.Color.toHex(hslString);

            satValue.set('text', s + '%');
            lumValue.set('text', l + '%');

            color.setStyle('backgroundColor', hexString);

            updateOutput(hslString);
        }
        
        var hexOutput = Y.one('#cathex-output'),
        focused = null;

        hexOutput.on('focus', setFocused);
        hexOutput.on('blur', unsetFocused);
        hexOutput.on('valueChange', updatePickerFromValue);

        
        function updateOutput(hslString) {
            if (hexOutput !== focused) {
                hexOutput.set('value', Y.Color.toHex(hslString));
            }

        }

        function updatePickerFromValue(e) {
            var val = e.newVal,
                hsl = [];

            if (Y.Color.toArray(val)) {
                hsl = Y.Color.toArray(Y.Color.toHSL(val));
                setPickerUI({
                    h: hsl[0],
                    s: hsl[1],
                    l: hsl[2]
                });
            }
        }

        function setFocused(e) {
            focused = e.currentTarget;
        }

        function unsetFocused(e) {
            if (focused === e.currentTarget) {
                focused = null;
            }
        }
        
        updatePickerUI();

    },
    /**
     * Creates a new category.  If successful, will also render the corresponding 
     * category node and category label.
     * 
     */
    create_category: function() {
        var name = Y.one('#catname-output').get('value');
        var color = Y.one('#cathex-output').get('value').replace('#', '');
      
        var alert = Y.one('#create-category-dialog .alert');

        if (alert) {
            alert.remove(true);
        }

        var data = {
            name: name,
            color: color
        };

        var validate = false;
        Y.io(M.cfg.wwwroot + '/local/ucla_support_tools/rest.php', {
            method: 'POST',
            data: {
                action: 'createcategory',
                json: JSON.stringify(data),
                sesskey: M.cfg.sesskey
            },
            on: {
                success: function (id, result) {
                    //
                    var data = JSON.parse(result.responseText);

                    if (data.status) {
                        // Create the category node.
                        var html = '<li>' + data.category_html + '</li>';
                        Y.one('.ucla-support-tool-category-list ul').appendChild(html);
                        // Attach drag & drop events.
                        M.local_ucla_support_tools.dragdrop.set_drop_events(Y.one('.ucla-support-category[data-id="' + data.id + '"]'))
                        // Create the category label.
                        html = '<li>' + data.category_label + '</li>';
                        Y.one('.ucla-support-tool-category-labels ul').appendChild(html);
                    } else {
                        if (data.error.msg.indexOf('Error writing to database') !== -1) {
                            Y.one('#create-category-dialog h3.title').insert('<div class="alert alert-warning" role="alert"><strong>Unable to create category!</strong> Perhaps you already have a category with the same name?</div>', 'after');
                        }
                    }

                    validate = data.status;
                }
            },
            sync: true
        });

        return validate;
    },
    /**
     * Deletes a category.  If successful, will delete the associated nodes.
     * 
     * @param {int} id
     */
    delete_category: function (id) {
        var dialog = M.local_ucla_support_tools.dialog.create({
            id: 'delete-category-dialog',
            title: "Delete category",
            body: "Are you sure you want to delete this category?",
            cancel: "Cancel",
            proceed: "Delete category"
        });

        dialog.callback = function () {
            Y.log('Removing category with ID: ' + id, 'info', 'local_ucla_support_tools');

            Y.io(M.cfg.wwwroot + '/local/ucla_support_tools/rest.php', {
                method: 'POST',
                data: {
                    action: 'deletecategory',
                    json: JSON.stringify({id: id}),
                    sesskey: M.cfg.sesskey
                },
                on: {
                    success: function (id, result) {
                        var data = JSON.parse(result.responseText);

                        if (data.status) {
                            Y.one('.ucla-support-category[data-id="' + data.id + '"]').ancestor('li').remove(true);
                            Y.one('.ucla-support-category-header[data-id="' + data.id + '"]').ancestor('li').remove(true);
                        }
                    }
                }
            });

            return true;
        };

        dialog.show();
    },
    /**
     * Adds a tool to a category.
     * 
     *      data: {
     *          toolid: @int,
     *          catid: @int
     *      }
     *
     * @param {obj} data 
     */
    add_tool: function(data) {

        Y.io(M.cfg.wwwroot + '/local/ucla_support_tools/rest.php', {
            method: 'POST',
            data: {
                action: 'addtooltocategory',
                sesskey: M.cfg.sesskey,
                json: JSON.stringify(data)
            }
        });
    },
    /**
     * Removes a tool from a category.
     * 
     *      data: {
     *          toolid: @int,
     *          catid: @int
     *      }
     *
     * @param {obj} data 
     */
    remove_tool: function(data) {
        Y.io(M.cfg.wwwroot + '/local/ucla_support_tools/rest.php', {
            method: 'POST',
            data: {
                action: 'removetoolfromcategory',
                sesskey: M.cfg.sesskey,
                json: JSON.stringify(data)
            },
            on: {
                    success: function (id, result) {
                        var data = JSON.parse(result.responseText);

                        if (data.status) {
                            Y.one('.ucla-support-category[data-id="' + data.catid + '"] .ucla-support-tool[data-id="' + data.toolid + '"]').ancestor('li').remove(true);
                        }
                    }
                }
        });
        return true;
    }
};