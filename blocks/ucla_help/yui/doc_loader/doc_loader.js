/**
 * 
 * YUI script to load CCLE wiki docs into sidebar
 * 
 */

YUI.add('moodle-block_ucla_help-doc_loader', function(Y) {

    var ModulenameNAME = 'blocks_ucla_help_loader';
    var SIDEBAR = null;

    var SIDEBAR_TEMPLATES = {
        DEFAULT : '<div class="ui sidebar doc help"></div>',
        HEADER: '<h2 class="ui header">\
                        <span class="glyphicon glyphicon-ok-sign"></span>\
                        {title}\
                 </h2>',
        CLOSE_BUTTON: '<button class="btn btn-info help-toggle btn-block">\
                            Hide help topic\
                       </button>',
        ACCORDION: '<div class="ui basic accordion"></div>',
        ACCORDION_CONTENT: '<div class="title">{title}</div>\
                            <div class="content">{content}</div>'
    }
    var MODULENAME = function() {
        MODULENAME.superclass.constructor.apply(this, arguments);
    };

    Y.extend(MODULENAME, Y.Base, {
        initializer : function(config) { // 'config' contains the parameter values
            // Create the sidebar
            SIDEBAR = Y.Node.create(SIDEBAR_TEMPLATES.DEFAULT);

            var help_button = Y.Node.create(SIDEBAR_TEMPLATES.CLOSE_BUTTON);
            help_button.on('click', function(e) {
                 $('.main.help.sidebar').sidebar('toggle');
//                 $('.doc.help.sidebar').sidebar('toggle');
                 SIDEBAR.one('.ui.accordion').destroy();
            });

            SIDEBAR.append(help_button);
            SIDEBAR.append(Y.Node.create('<h2 class="ui dividing header"></h2>'));
            SIDEBAR.append(Y.Node.create('<div class="ui stacked segment"><div class="ui basic accordion"></div></div>'));

            Y.one('body').appendChild(SIDEBAR);

            // Attach events to help doc topics
            Y.all('.topics .item').on('click', function (e) {
                e.preventDefault();

                var target = e.target;

                Y.io(M.cfg.wwwroot + '/blocks/ucla_help/sidebar/docs/rest.php?title=' + encodeURIComponent(target.getAttribute('data-title')), {
                    on: {
                        success: function(tx, r) {
                            // Get json data
                            var data = Y.JSON.parse(r.responseText);

                            // Prepare accordion
                            var accordion = Y.Node.create(SIDEBAR_TEMPLATES.ACCORDION);

                            // This replaces the previous accordion node
                            SIDEBAR.one('.segment .accordion').replace(accordion);

                            // Now append our data
                            accordion.append(data.content);

                            // Set the title
                            SIDEBAR.one('.ui.header').set('text', target.getAttribute('data-title'));

                            // Open the sidebar
                            $('.sidebar.doc').sidebar('toggle');
                            // Attach the accordion handler
                            $('.ui.accordion').accordion();
                        }
                    }
                });
            });
        }
    }, {
        NAME : ModulenameNAME,
        ATTRS : {
                 aparam : {}
        }
    });

    M.blocks_ucla_help = M.blocks_ucla_help || {};
    M.blocks_ucla_help.init = function(config) {
        return new MODULENAME(config); // 'config' contains the parameter values
    };
}, '@VERSION@', {
  requires: ['base', 'node', 'io', 'json-parse', 'jsonp']
});