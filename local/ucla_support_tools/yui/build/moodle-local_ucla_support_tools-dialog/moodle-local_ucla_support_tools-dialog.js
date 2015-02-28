YUI.add('moodle-local_ucla_support_tools-dialog', function (Y, NAME) {



M.local_ucla_support_tools = M.local_ucla_support_tools || {};


/**
 * 
 * @type obj
 */
M.local_ucla_support_tools.dialog = {
    
    /**
     * Factory method to generate a very generic dialog with Y.Panel.
     * 
     *      config: {
     *          id: @css_id,
     *          title: @string,
     *          body: @html,
     *          cancel: @string,
     *          proceed: @string
     *      }
     * 
     * @param {obj} config configuration
     * @returns {Y.Panel}
     */
    create: function(config) {

        var panel_config = {
            contentBox : Y.Node.create('<div id="' + config.id + '" class="ucla-tools-dialog" />'),
            bodyContent: Y.Lang.sub('<h3 class="title">{title}</h3><div class="message">{body}</div>', config),
            width      : 400,
            zIndex     : 999,
            centered   : true,
            modal      : true, // modal behavior
            render     : 'body',
            visible    : false, // make visible explicitly with .show()
            buttons    : {
                footer: [
                    {
                        name  : 'cancel',
                        label : config.cancel,
                        action: 'cancel'
                    },
                    {
                        name     : 'proceed',
                        label    : config.proceed,
                        action   : 'proceed'
                    }
                ]
            },
            // Disable 'esc' key event.  This does not clean up correctly and
            // It's not clear how this event can be captured and processed, so 
            // removing it to be safe.
            hideOn: []
        };
        
        var panel = new Y.Panel(panel_config);

        panel.cancel = function (e) {
            this.hide();
            this.callback = null;
            this.reset();
            this.destroy();
        };
        panel.proceed = function (e) {

            // Simple validation against empty values.
            if (this.validate && !M.local_ucla_support_tools.dialog.validate(this.validate())) {
                return;
            }

            // The callback acts as a second validation step.
            if (this.callback && !this.callback()) {
                return;
            }

            this.hide();
            this.callback = null;
            this.reset();
            this.destroy();
        };

        return panel;
    },
    /**
     * Will generate a form given a set of data that describes the desired inputs.
     * 
     *      data: [
     *          {
     *              name: @string,
     *              label: @string,
     *              placeholder: @string
     *          }
     *      ]
     * 
     * @param {Y.ArrayList} data
     * @returns {Y.Node} form
     */
    form_with_inputs: function (data) {

        var form = Y.Node.create('<form></form>');

        data.each(function (input) {
            var node = '<div class="form-group">' +
                    '<label for="{name}-output">{label}</label>' +
                    '<input id="{name}-output" class="form-control" type="text" placeholder="{placeholder}" />' +
                    '</div>';

            form.appendChild(Y.Node.create(Y.Lang.sub(node, input)));
        });

        return form;
    },
    /**
     * Validates a set of input nodes.  If validation fails, will style the
     * input to show error.
     * 
     *      nodes: [
     *          Y.Node
     *      ]
     * 
     * @param {Y.ArrayList} nodes
     * @returns {bool} result
     */
    validate: function (nodes) {

        var result = false;

        nodes.each(function (node) {
            if (node.get('value') === '') {
                node.ancestor('.form-group').addClass('has-error');
                result = false;
            } else {
                node.ancestor('.form-group').removeClass('has-error');
                result = true;
            }
        });

        return result;
    }
};

}, '@VERSION@', {"requires": ["base", "node", "panel", "event-key"]});
