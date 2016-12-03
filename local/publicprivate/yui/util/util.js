YUI.add('moodle-local_publicprivate-util', function(Y) {

    var CSS = {
        ACTIVITYLI : 'li.activity',
        COMMANDDIV : 'div.commands',
        GROUPINGSPAN : 'span.groupinglabel',
        MODINDENTDIV : 'div.mod-indent',
        PAGECONTENT : 'div#page-content',
        PUBLICPRIVATE_PRIVATE : 'a.editing_makeprivate',
        PUBLICPRIVATE_PUBLIC : 'a.editing_makepublic',
        DUPLICATE : 'a.editing_duplicate',
        ASSIGNROLES : 'a.editing_assign',
        GROUPMODEFILLER : 'span.filler',
        SPINNERCOMMANDDIV : 'div.commands',
        MODULEIDPREFIX : 'module-',
        PUBLICPRIVATEIMG : 'a.publicprivate img',
        PUBLICPRIVATETEXT : 'a.publicprivate span.menu-action-text'
    };
    
    var PUBLICPRIVATE = function() {
        PUBLICPRIVATE.superclass.constructor.apply(this, arguments);
    }

    Y.extend(PUBLICPRIVATE, Y.Base, {
        initializer : function(config) {
            // Set event listeners
            Y.delegate('click', this.toggle, CSS.PAGECONTENT, CSS.COMMANDDIV + ' a.publicprivate', this);

            // Let moodle know we exist
            M.course.coursebase.register_module(this);
        },
        toggle : function(e) {
            e.preventDefault();
    
            var mod = e.target.ancestor(CSS.ACTIVITYLI);
            
            var field = '';
            var instance = mod.one('.activityinstance');
            // If an activity instance is not found, use the div enclosing the entire module
            if (!instance) {
                instance = mod.one('.mod-indent-outer');
            }
            // TODO This currently won't work if there are conditions besides the publicprivate one
            var privateGrouping = instance.one('.availabilitypopup');
            
            if (privateGrouping) {
                privateGrouping.remove();
                field = 'public';

                // Swap icon
                mod.one(CSS.PUBLICPRIVATEIMG).setAttrs({
                    'src' : M.util.image_url(this.get('publicpix'), this.get('component')),
                    'alt' : M.util.get_string('publicprivatemakeprivate', 'local_publicprivate')
                })
                // Change text
                if(mod.one(CSS.PUBLICPRIVATETEXT)) {
                    mod.one(CSS.PUBLICPRIVATETEXT).set('text', M.util.get_string('publicprivatemakeprivate', 'local_publicprivate'));
                }
            } else {
                // Add popup
                var popupContents = Y.Escape.html('<div class=\"availabilityinfo\">Not available unless: You belong to a group in <strong>Private Course Material</strong> (hidden otherwise)</div>');
                var popup = '<span class="groupinglabel availabilitypopup" data-availabilityconditions="' + popupContents + '"><a aria-haspopup="true" href="#">Access restrictions</a></span>';
                instance.insert(Y.Node.create(popup));
                
                // Swap icon
                mod.one(CSS.PUBLICPRIVATEIMG).setAttrs({
                    'src' : M.util.image_url(this.get('privatepix'), this.get('component')),
                    'alt' : M.util.get_string('publicprivatemakepublic', 'local_publicprivate')
                });
                // Change text
                if(mod.one(CSS.PUBLICPRIVATETEXT)) {
                    mod.one(CSS.PUBLICPRIVATETEXT).set('text', M.util.get_string('publicprivatemakepublic', 'local_publicprivate'));
                }
                field = 'private'; 
            }
            
            // Prepare ajax data
            var data = {
                'class' : 'resource',
                'field' : field,
                'id'    : mod.get('id').replace(CSS.MODULEIDPREFIX, '')
            };
            
            // Get spinner
            var spinner = M.util.add_spinner(Y, mod.one(CSS.SPINNERCOMMANDDIV));

            // Send request
            this.send_request(data, spinner);
        },
        send_request : function(data, statusspinner) {
            // Default data structure
            if (!data) {
                data = {};
            }

            data.sesskey = M.cfg.sesskey;
            data.courseId = this.get('courseid');

            var uri = M.cfg.wwwroot + '/local/publicprivate/rest.php';

            // Define the configuration to send with the request
            var responsetext = [];
            var config = {
                method: 'POST',
                data: data,
                on: {
                    success: function(tid, response) {
                        try {
                            responsetext = Y.JSON.parse(response.responseText);
                            if (responsetext.error) {
                                new M.core.ajaxException(responsetext);
                            }
                        } catch (e) {}
                        if (statusspinner) {
                            window.setTimeout(function(e) {
                                statusspinner.hide();
                            }, 400);
                        }
                    },
                    failure : function(tid, response) {
                        if (statusspinner) {
                            statusspinner.hide();
                        }
                        new M.core.ajaxException(response);
                    }
                },
                context: this,
                sync: true
            }

            if (statusspinner) {
                statusspinner.show();
            }

            // Send the request
            Y.io(uri, config);
            return responsetext;
        },
        setup_for_resource : function(newnode) {
            // Get the module number.
            var modnumber = (newnode.getAttribute('id')).replace(CSS.MODULEIDPREFIX,'');
            // Create href for the new node.
            var href = M.cfg.wwwroot + 
                    '/local/publicprivate/mod.php?' + 
                    M.cfg.sesskey + '&public=' + 
                    modnumber;

            // Generate publicprivate icon node after duplicate node.
            // NOTE: because we delegate events, we don't need to attach a handler.
            newnode.one('.menu ' + CSS.DUPLICATE).insert(
                Y.Node.create(
                    '<li role="presentation"> ' +
                        '<a class="editing_makepublic publicprivate menu-action cm-edit-action" ' +
                            'href="' + href + '"' +
                            'role="menuitem">' +
                            '<img class="iconsmall" ' +
                                'src="' + M.util.image_url(this.get('privatepix'), this.get('component')) + '"' +
                                'alt="' + M.util.get_string('publicprivatemakepublic', 'local_publicprivate') + '"/>' +
                            '<span class="menu-action-text">' +
                                M.util.get_string('publicprivatemakepublic', 'local_publicprivate') +
                            '</span>' +
                        '</a>' +
                    '</li>'
                ), 'after'
            );
            // Remove Assign roles.
            newnode.one(".menu " + CSS.ASSIGNROLES).remove();
            // Remove filler node for groupmode.
            newnode.one(CSS.GROUPMODEFILLER).remove();
        }
    }, {
        NAME : 'course-publicprivate-toolbox',
        ATTRS : {
            courseid : {
                'value' : 0
            },
            component : {
                'value' : 'core'
            },
            privatepix : {
                'value' : 't/locked'
            },
            publicpix : {
                'value' : 't/lock'
            }
        }
    });
    
    M.local_publicprivate = M.local_publicprivate || {};
    
    M.local_publicprivate.init = function (params) {
        // Load module
        return new PUBLICPRIVATE(params);
    }
    
},
'@VERSION@', {
    requires : ['node', 'io', 'moodle-course-coursebase']
}
);
