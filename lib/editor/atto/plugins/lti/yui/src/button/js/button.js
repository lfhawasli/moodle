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
 * @package     atto_lti
 * @copyright   2020 The Regents of the University of California
 * @author      David Shepard
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * @module moodle-atto_lti-button
 */


/**
 * Atto text editor LTI activities plugin
 *
 * @namespace M.atto_lti
 * @class     Button
 * @extends    M.editor_atto.EditorPlugin
 */


require(['core/str'], function (str) {

    var errorMessage = null,
        stringPromise = str.get_string('erroroccurred', 'atto_lti');

    $.when(stringPromise).done(function (invalid) {
        errorMessage = invalid;
    });

    document.CALLBACKS = {
        handleError: function (errors) {
            alert(errorMessage);
            for (var i = 0; i < errors.length; i++) {
                console.error(errors[i]);
            }
        }
    };

});

Y.namespace('M.atto_lti').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {

    _CREATEACTIVITYURL: M.cfg.wwwroot + '/lib/editor/atto/plugins/lti/view.php',
    _CONTENT_ITEM_SELECTION_URL: M.cfg.wwwroot + '/lib/editor/atto/plugins/lti/contentitem.php',

    _panel: null,

    _addTool: function (event, tool) {
        event.preventDefault();
        var resourceLinkId = this._createResourceLinkId(),
            host = this.get('host'),
            panel,
            courseid = this._course;

        document.CALLBACKS['f' + resourceLinkId] = function (contentItemData) {
            if (!contentItemData) {
                return;
            }

            panel.hide();
            for (var i = 0; i < contentItemData['@graph'].length; i++) {
                var item = contentItemData['@graph'][i];
                var strategyFactory = new Y.M.atto_lti.PlacementStrategyFactory();
                var strategy = strategyFactory.strategyFor(item, courseid, resourceLinkId, tool);
                var render = strategy.toHtml;
                host.insertContentAtFocusPoint(render(item));
            }
            host.saveSelection();
            host.updateOriginal();
            panel.destroy();
        };

        this._panel = new M.core.dialogue({
            bodyContent: '<iframe src="' + this._CONTENT_ITEM_SELECTION_URL +
                '?course=' + this._course.id +
                '&id=' + tool.id +
                '&callback=f' + resourceLinkId +
                '" width="100%" height="100%"></iframe>',
            headerContent: tool.name,
            width: '67%',
            height: '66%',
            draggable: false,
            visible: true,
            zindex: 100,
            modal: true,
            focusAfterHide: host.editor,
            focusOnPreviousTargetAfterHide: true,
            render: true
        });

        this._panel.after('visibleChange', function(){this._panel.destroy();}, this);

        panel = this._panel;

    },

    initializer: function (arg) {
        
        if(arg.toolTypes.length > 0){
            this._course = arg.course;

            this._createResourceLinkId = (function (base) {
                return function () {
                    return base + '_' + (new Date()).getTime();
                };
            }(arg.resourcebase));
            this.addToolbarMenu({

                icon: M.util.image_url('icon', 'mod_lti'),

                globalItemConfig:{
                    callback: this._addTool
                },

                items: arg.toolTypes.map(function (arg) {
                    return {
                        text : arg.name,
                        callbackArgs: arg
                    };
                })
            });
        }

    }

});