/**
 * YUI module to add warning messages to the "Delete and then restore" radio
 * buttons on the page.
 */

YUI.add('moodle-local_ucla-restoreoverwritewarning', function(Y) {

    // Namespace for the backup.
    M.core_backup = M.core_backup || {};

    // Generate a moodle ajax dialog.
    M.core_backup.course_deletion_warning = function(courseid, section) {
        Y.io(M.cfg.wwwroot.concat('/local/ucla/rest_additionalcoursecontent.php'), {
            method: 'GET',
            data: 'courseid=' + courseid,
            on: {
                success: function(id, result) {
                    var json = Y.JSON.parse(result.responseText);

                    if (json.status) {

                        // Create the confirm box.
                        var confirm = new M.core.confirm(json.config);

                        // If the user clicks "Backup", direct users to backup the course.
                        confirm.on('complete-yes', function(e) {
                            window.open(json.config.url, '_self');
                        }, this);
                        confirm.on('complete-no', function(e) {
                            Y.one('.' + section + ' input[value="Continue"]').simulate('click');
                        }, this);
                        // Show the confirm box.
                        confirm.show();
                    }

                }
            },
            failure: function() { }
        });
    };

    M.core_backup.course_deletion_check = function(param) {

        // Attach this to 'Restore into an existing course' radio nodes.
        Y.one('.bcs-existing-course .generaltable').delegate('click', function(e) {

            // Make sure radio button has a courseid.
            var courseid = e.target.getAttribute('value');
            // Make sure that 'delete' radio is checked.
            var radiodelete = Y.one('.bcs-existing-course .detail-pair:nth-of-type(2) [type="radio"]:checked');

            // If both conditions, then check that course has content.
            if (courseid && radiodelete) {
                M.core_backup.course_deletion_warning(courseid, 'bcs-existing-course');
            }

        }, 'input[type=radio]');

        // It may be the case that user selects a course first, then selects delete radio.
        Y.one('.bcs-existing-course .detail-pair:nth-of-type(2) [type="radio"]').on('click', function(e) {
            var selectedradio = Y.one('.bcs-existing-course .generaltable [type="radio"]:checked');
            if (selectedradio) {
                var courseid = selectedradio.getAttribute('value');
                M.core_backup.course_deletion_warning(courseid, 'bcs-existing-course');
            }
        });

        // Attach to 'Restore into this course' radio node.
        Y.one('.bcs-current-course .detail-pair:nth-of-type(2) [type="radio"]').on('click', function(e) {
            M.core_backup.course_deletion_warning(param.courseid, 'bcs-current-course');
        });

    };

}, '@VERSION@', {'requires':['io','json','base','node','event','node-event-simulate','moodle-core-notification']});
