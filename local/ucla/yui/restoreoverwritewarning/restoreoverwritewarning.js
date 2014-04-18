/*
 * YUI module to add warning messages to the "Delete and then restore" radio buttons on the page.
 *
 */

YUI.add('moodle-local_ucla-restoreoverwritewarning', function(Y) {

// Namespace for the backup
M.core_backup = M.core_backup || {};

M.core_backup.watch_current_delete_button = function(param) {
    Y.one('.restore-current-delete').on('click', function(e) {
        // Create the confirm box
        var confirm = new M.core.confirm(param.config);
        // If the user clicks "Backup", direct users to backup the course
        confirm.on('complete-yes', function(e) {
            window.open(param.url, "_self");
        }, this);
        // Show the confirm box
        confirm.show();
    });
};

var SITE = M.cfg.wwwroot;
var PAGE = { COURSECONTENT: '/local/ucla/rest_additionalcoursecontent.php' };

M.core_backup.watch_existing_delete_button = function() {
    var restore_tables = Y.all('.generaltable');
    restore_tables.item(1).addClass('restore-existing-course-search');

    Y.all('.restore-existing-course-search input[type="radio"]').each( function(node) {
        node.on('click', function(e) {
            var radio = e.target;
            var existingcourseid = radio.getAttribute('value');

            var radiodelete = Y.one('.restore-existing-delete[type="radio"]:checked');
            if (radiodelete) {
                Y.io(SITE.concat(PAGE.COURSECONTENT), {
                    method: 'GET',
                    data: 'courseid='+existingcourseid,
                    on: {
                        success: function(id, result) {
                            var json = Y.JSON.parse(result.responseText);

                            // Create the config object
                            var config = {
                                title: json.title, yesLabel: 'Backup', question: json.message,
                                noLabel: 'Continue', closeButtonTitle: json.closeButtonTitle
                            };

                            // Create the confirm box
                            var confirm = new M.core.confirm(config);
                            // If the user clicks "Backup", direct users to backup the course
                            confirm.on('complete-yes', function(e) {
                                window.open(json.url, "_self");
                            }, this);
                            // Show the confirm box
                            confirm.show();
                        }
                    },
                    failure: function() { }
                });
            }
        });
    });
};

}, '@VERSION@', {'requires':['io','json','base','node','event','node-event-simulate','moodle-core-notification']});
