YUI.add('moodle-local_ucla-submissionstatus', function (Y) {

    var COLLAPSIBLEROWS = Array(
            M.util.get_string('duedate', 'assign'),
            M.util.get_string('timeremaining', 'assign'),
            M.util.get_string('editingstatus', 'assign'),
            M.util.get_string('timemodified', 'assign')
    );

    var TOGGLELINK;
    var TARGETROWS;
    var TABLE;

    var CSS = {
        COLLAPSED: 'submissionsummarytable-collapsed'
    };

    // Load our namespace
    M.local_ucla = M.local_ucla || {};

    // Attach script
    M.local_ucla.submissionstatus = {  
        init: function() {
            TABLE = Y.one('.submissionsummarytable');
            var tbody = TABLE.one('> table > tbody');

            // Iterate over the rows to hide and store
            // rows that should be hidden
            TARGETROWS = new Array();
            var rowcontent;
            tbody.get('children').each(function(row) {
               rowcontent = row.one('*').get('innerHTML');
               if (COLLAPSIBLEROWS.indexOf(rowcontent) >= 0) {
                   TARGETROWS.push(row);
                   row.hide();
               }
            });

            // Initialize the toggle button to collapsed state and insert it above the table
            TOGGLELINK = Y.Node.create(
                   '<div class="collapsible-actions">\
                        <a href="#" class="action-icon collapseexpand submissionsummarytabletoggle">Expand</a>\
                    </div>'
                    );
            Y.one('.submissionstatustable').insert(TOGGLELINK, TABLE);
            TABLE.addClass(CSS.COLLAPSED);

            // Attach an event to the toggle link
            TOGGLELINK.on('click', function(e) {
                e.preventDefault();
                M.local_ucla.submissionstatus.toggle();        
            });
        },

        toggle: function() {

            if (TABLE.hasClass(CSS.COLLAPSED)) {
                TABLE.removeClass(CSS.COLLAPSED);
                TOGGLELINK.setHTML('<a href="#" class="action-icon collapseexpand collapse-all submissionsummarytabletoggle">Collapse</a>');
            } else {
                TABLE.addClass(CSS.COLLAPSED);
                TOGGLELINK.setHTML('<a href="#" class="action-icon collapseexpand submissionsummarytabletoggle">Expand</a>');
            }

            // Hide or show rows
            for(var i=0; i < TARGETROWS.length; i++) {
                TARGETROWS[i].toggleView();
            }
        }
    };

}, '@VERSION@', { requires: ['event'] });
