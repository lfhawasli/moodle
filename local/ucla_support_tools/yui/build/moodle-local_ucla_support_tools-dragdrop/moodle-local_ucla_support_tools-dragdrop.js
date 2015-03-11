YUI.add('moodle-local_ucla_support_tools-dragdrop', function (Y, NAME) {

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

M.local_ucla_support_tools.dragdrop = {

    init: function () {

        var drop_nodes = Y.all('.ucla-support-category:not(.favorites)');

        var del = new Y.DD.Delegate({
            container: '.ucla-support-tool-alltools ul',
            nodes: 'li'
        });

        del.on('drag:start', function (e) {
            e.target.get('node').setStyle('opacity', '.5');
        });
        del.on('drag:end', function (e) {
            e.target.get('node').setStyle('opacity', '1');
        });

        del.dd.plug(Y.Plugin.DDConstrained, {
            constrain2node: '#region-main-box'
        });

        del.dd.plug(Y.Plugin.DDProxy, {
            moveOnEnd: false,
            cloneNode: true
        });

        drop_nodes.each(this.set_drop_events);
    },
    set_drop_events: function (node) {
            node.plug(Y.Plugin.Drop);
            node.drop.on('drop:over', function (e) {
                node.setStyle('borderColor', '#f3a025');
            });
            node.drop.on('drop:exit', function (e) {
                node.setStyle('borderColor', '#d1d4d5');
            });
            node.drop.on('drop:hit', function (e) {

                var toolid = e.drag.get('node').one('.ucla-support-tool').getData('id');
                // Add tool.
                M.local_ucla_support_tools.categoryorganizer.add_tool({
                    catid: node.getData('id'),
                    toolid: toolid
                });

                if (!node.one('.ucla-support-tool[data-id="' + toolid + '"]')) {
                    node.one('ul').appendChild(Y.Node.create('<li>' + e.drag.get('node').get('innerHTML') + '</li>'));
                }

                var anim = new Y.Anim({
                    node: node,
                    from: {
                        borderColor: '#27ae60'
                    },
                    to: {borderColor: '#d1d4d5'},
                    duration: 1.25
                });
                anim.run();
            });
        }
};

}, '@VERSION@', {"requires": ["dd-drop", "dd-constrain", "dd-delegate", "dd-drop-plugin", "dd-proxy", "anim"]});
