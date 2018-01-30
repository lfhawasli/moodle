/**
 * When a submission type checkbox is checked, list it's relevent sub
 * options only. Otherwise, hide them.
 *
 */
YUI.add('moodle-local_ucla-submissiontype', function (Y) {
    // Load our namespace.
    M.local_ucla = M.local_ucla || {};

    // Attach script.
    M.local_ucla.submissiontype = {
        init: function() {
            var mediagallery = Y.one('#fitem_id_assignsubmission_mediagallery_mg');
            var mediagallerycheck = Y.one('#id_assignsubmission_mediagallery_enabled');

            var maxfilesub = Y.one('#fitem_id_assignsubmission_file_maxfiles');
            var maxfilesize = Y.one('#fitem_id_assignsubmission_file_maxsizebytes');
            var filesubmitcheck = Y.one('#id_assignsubmission_file_enabled');

            var recordertype = Y.one('#fitem_id_assignsubmission_onlinepoodll_recordertype');
            var timelimit = Y.one('#fitem_id_assignsubmission_onlinepoodll_timelimit');
            var onlinepoodllcheck = Y.one('#id_assignsubmission_onlinepoodll_enabled');

            var wordlimit = Y.one('#fgroup_id_assignsubmission_onlinetext_wordlimit_group');
            var onlinetextcheck = Y.one('#id_assignsubmission_onlinetext_enabled');

            // Handle visibility of sub options when Media collection type checked.
            if (mediagallerycheck != null) {
                if (mediagallerycheck.get('checked')) {
                    mediagallery.show();
                } else {
                    mediagallery.hide();
                }
                mediagallerycheck.on('change', function (e) {
                    mediagallery.toggleView();
                });
            }

            // Handle visibility of sub options when Online Poodll type checked.
            if (onlinepoodllcheck != null) {
                if (onlinepoodllcheck.get('checked')) {
                    recordertype.show();
                    timelimit.show();
                } else {
                    recordertype.hide();
                    timelimit.hide();
                }
                onlinepoodllcheck.on('change', function (e) {
                    recordertype.toggleView();
                    timelimit.toggleView();
                });
            }

            // Handle visibility of sub options when Online Text type checked.
            if (onlinetextcheck != null) {
                if (onlinetextcheck.get('checked')) {
                    wordlimit.show();
                } else {
                    wordlimit.hide();
                }
                onlinetextcheck.on('change', function (e) {
                    wordlimit.toggleView();
                });
            }
        },
    };
}, '@VERSION@', {'requires':['node','event']});
