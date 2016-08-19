/**
  * When a Feedback type checkbox is checked, list it's relevant sub
  * options only. Otherwise, hide them.
  *
**/
YUI().add('moodle-local_ucla-feedbacktype', function (Y) {
    // Load our namespace.
    M.local_ucla = M.local_ucla || {};

    // Attach script.
    M.local_ucla.feedbacktype = {
        init: function() {
            var feedbackcomments = Y.one('#fitem_id_assignfeedback_comments_commentinline');
            var feedbackcommentscheck = Y.one('#id_assignfeedback_comments_enabled');

            var feedbackPoodllrecorder = Y.one('#fitem_id_assignfeedback_poodll_recordertype');
            var feedbackPoodlldownload = Y.one('#fitem_id_assignfeedback_poodll_downloadsok');
            var feedbackPoodllcheck = Y.one('#id_assignfeedback_poodll_enabled');

            // Handle visibility of sub options when Feedback comments type checked.
            if (feedbackcommentscheck.get('checked')) {
                feedbackcomments.show();
            } else {
                feedbackcomments.hide();
            }
            feedbackcommentscheck.on('change', function (e) {
                feedbackcomments.toggleView();
            });

            // Handle visibility of sub options when Feedback PoodLL type checked.
            if (feedbackPoodllcheck.get('checked')) {
                feedbackPoodllrecorder.show();
                feedbackPoodlldownload.show();
            } else {
                feedbackPoodllrecorder.hide();
                feedbackPoodlldownload.hide();
            }
            feedbackPoodllcheck.on('change', function (e) {
                feedbackPoodllrecorder.toggleView();
                feedbackPoodlldownload.toggleView();
            });
        },
    };
}, '@VERSION@', {'requires':['node','event']});
