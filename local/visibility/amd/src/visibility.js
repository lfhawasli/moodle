define(['jquery', 'core/notification', 'core/str'], function($, notification, str) {
    // Private variables and functions.
    
    /** @var {Number} element The current clicked element */
    var element = 0;
    
    /**
     * Displays a confirmation dialog box for deleting visibility session.
     *
     * @method confirmationDialog
     */
    var confirmationDialog = function() {
        // Create confirmation string dialog
        str.get_strings([
            { key: 'confirm', component: 'moodle' },
            { key: 'confirmremovevisibilitysession', component: 'local_visibility' },
            { key: 'yes', component: 'core' },
            { key: 'no', component: 'core' }
        ]).done(function(strs) {
            notification.confirm(
                strs[0], // Confirm
                strs[1], // Are you absolutely sure?
                strs[2], // Yes
                strs[3], // No
                deleteSession // On Confirm
            );
        }.bind(this)).fail(notification.exception);
    };
    
    /**
     * Deletes selected visibility session via ajax
     * 
     * @method deleteSession
     */
    var deleteSession = function(){
        var id = element.data('id');
        var courseId = element.data('course');
        
        $.ajax({
            url: "ajax.php",
            data: {
                action: 'delete',
                courseid: courseId,
                rangeid: id
            }
        }).done(function( msg ) {
            if (msg.success) {
                $(".range" + id).fadeOut(250);
            } else {
                // Deletion failed. Display an error message.
                var moodleStringPromise = str.get_string('deleteerror', 'local_visibility');
                $.when(moodleStringPromise).done(function(errorMsg) {
                    window.alert(errorMsg);
                });
            }
        });
    };
   
    return {
        // Public variables and functions
        
        /**
         * Initialise the module.
         * @method init
         */
        init: function() {
            $('.rangedeletebutton').click(function(ev) {
                element = $(this);
                ev.preventDefault();
                confirmationDialog();
            });
        }
    };
});