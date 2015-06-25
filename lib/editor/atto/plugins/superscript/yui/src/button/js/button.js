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

/*
 * @package    atto_superscript
 * @copyright  2014 Rosiana Wijaya <rwijaya@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module moodle-atto_superscript-button
 */

/**
 * Atto text editor superscript plugin.
 *
 * @namespace M.atto_superscript
 * @class button
 * @extends M.editor_atto.EditorPlugin
 */

Y.namespace('M.atto_superscript').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {
    /**
     * A rangy object to alter CSS classes.
     *
     * @property _subscriptApplier
     * @type Object
     * @private
     */
    _subscriptApplier: null,

    /**
     * A rangy object to alter CSS classes.
     *
     * @property _superscriptApplier
     * @type Object
     * @private
     */
    _superscriptApplier: null,

    initializer: function() {
        this.addButton({
            buttonName: 'superscript',
            callback: this.toggleSuperscript,
            icon: 'e/superscript',

            // Watch the following tags and add/remove highlighting as appropriate:
            tags: 'sup'
        });
        this._subscriptApplier = window.rangy.createCssClassApplier("editor-subscript");
        this._superscriptApplier = window.rangy.createCssClassApplier("editor-superscript");
    },

    /**
     * Toggle superscripts in selection
     *
     * @method toggleSuperscript
     */
    toggleSuperscript: function() {
        // Check whether range is collapsed.
        var selection = window.rangy.getSelection();
        if (selection.isCollapsed) {
            var cursor = Y.one(this.get('host').getSelectionParentNode());
            this.get('host').editor.once('keypress', function() {
                // If position has changed, don't do anything.
                if (!cursor.compareTo(this.get('host').getSelectionParentNode())) {
                    return;
                }
                // Add an appropriate tag (not empty) to contain the character typed and select the contents.
                this.get('host').insertContentAtFocusPoint('<sup class="editor-new-content-container">0</sup>');
                var range = selection.getRangeAt(0);
                range.selectNodeContents(this.get('host').editor.one('.editor-new-content-container').getDOMNode());
                selection.setSingleRange(range);

                // After the keypress has inserted a character, change its level if necessary.
                Y.soon(Y.bind(function() {
                    // If the node was deleted, we don't need to add anything.
                    var newnode = this.editor.one('.editor-new-content-container');
                    if (!newnode) {
                        return;
                    }
                    // If the selection is now no longer in the newly added node remove it and quit.
                    if (newnode.compareTo(this.get('host').getSelectionParentNode())) {
                        newnode.remove(true);
                        return;
                    }

                    // If there are nested subscripts or superscripts they need to be toggled.
                    if (cursor.ancestor('sub, sup')) {
                        // Save the selection.
                        selection = window.rangy.saveSelection();

                        // Remove display:none from rangy markers so browser doesn't delete them.
                        this.editor.all('.rangySelectionBoundary').setStyle('display', null);

                        // Replace all the sub and sup tags.
                        this.changeToCSS();

                        // Select the new node.
                        this.get('host').setSelection(this.get('host')
                            .getSelectionFromNode(this.editor.one('.editor-new-content-container')));

                        // Clean up marker to prevent empty class.
                        this.editor.one('.editor-new-content-container').removeClass('editor-new-content-container');

                        // Remove all subscripts or superscripts containing new character.
                        this._subscriptApplier.undoToSelection();
                        this._superscriptApplier.undoToSelection();

                        // Replace CSS classes with tags.
                        this.changeToTags();

                        // Restore selection
                        window.rangy.restoreSelection(selection);
                    }
                    // Remove the selector class that is no longer needed.
                    this.editor.one('.editor-new-content-container').removeAttribute('class');
                }, this));
            }, this);
            return;
        }

        // The range is not collapsed; so use rangy to toggle the selection.
        this.changeToCSS();
        this._subscriptApplier.undoToSelection();
        this._superscriptApplier.toggleSelection();
        this.changeToTags();

    },

    /**
     * Replaces all the tags in a node list with new type.
     * @method replaceTags
     * @param NodeList nodelist
     * @param String tag
     */
    replaceTags: function(nodelist, tag) {
        // We mark elements in the node list for iterations.
        nodelist.setAttribute('data-iterate', true);
        var node = this.editor.one('[data-iterate="true"]');
        while (node) {
            var clone = Y.Node.create('<' + tag + ' />')
                .setAttrs(node.getAttrs())
                .removeAttribute('data-iterate');
            // Copy class and style if not blank.
            if (node.getAttribute('style')) {
                clone.setAttribute('style', node.getAttribute('style'));
            }
            if (node.getAttribute('class')) {
                clone.setAttribute('class', node.getAttribute('class'));
            }
            // We use childNodes here because we are interested in both type 1 and 3 child nodes.
            var children = node.getDOMNode().childNodes, child;
            child = children[0];
            while (typeof child !== "undefined") {
                clone.append(child);
                child = children[0];
            }
            node.replace(clone);
            node = this.editor.one('[data-iterate="true"]');
        }
    },

    /**
     * Change every sub and sub in editor to CSS class.
     * @method changeToCSS
     */
    changeToCSS: function() {
        // Save the selection.
        var selection = window.rangy.saveSelection();

        // Remove display:none from rangy markers so browser doesn't delete them.
        this.editor.all('.rangySelectionBoundary').setStyle('display', null);

        // Replace sub and sub tags with CSS classes.
        this.editor.all('sub').addClass('editor-subscript');
        this.editor.all('sup').addClass('editor-superscript');
        this.replaceTags(this.editor.all('.editor-superscript, .editor-subscript'), 'span');

        // Restore selection and toggle class.
        window.rangy.restoreSelection(selection);
    },

    /**
     * Change CSS classes in editor into sub and sub elmenents.
     * @method changeToCSS
     */
    changeToTags: function() {
        // Save the selection.
        var selection = window.rangy.saveSelection();

        // Remove display:none from rangy markers so browser doesn't delete them.
        this.editor.all('.rangySelectionBoundary').setStyle('display', null);

        // Replace spans with sub or sup.
        this.replaceTags(this.editor.all('.editor-superscript'), 'sup');
        this.replaceTags(this.editor.all('.editor-subscript'), 'sub');

        // Remove CSS classes.
        this.editor.all('[class="editor-subscript"], [class="editor-superscript"]').removeAttribute('class');
        this.editor.all('sub').removeClass('editor-subscript');
        this.editor.all('sup').removeClass('editor-superscript');

        // Restore selection and toggle class.
        window.rangy.restoreSelection(selection);
    }
});
