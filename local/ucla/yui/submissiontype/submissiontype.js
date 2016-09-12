/**
 * When a submission type checkbox is checked, list it's relevent sub 
 * options only. Otherwise, hide them.
 *
**/
YUI.add('moodle-local_ucla-submissiontype', function (Y) {   
    // Load our namespace
    M.local_ucla = M.local_ucla || {};

    // Attach script
    M.local_ucla.submissiontype = {  
        init: function() {
            var mediagallery = Y.one('#fitem_id_assignsubmission_mediagallery_mg');
            var mediagallerycheck = Y.one('#id_assignsubmission_mediagallery_enabled');

            var maxfilesub = Y.one('#fitem_id_assignsubmission_file_maxfiles');
            var maxfilesize = Y.one('#fitem_id_assignsubmission_file_maxsizebytes');
            var filesubmitcheck = Y.one('#id_assignsubmission_file_enabled');

            var usqfiletypescheck = Y.one('#id_assignsubmission_usqfiletypes_enabled');
            var usqfilelabel = usqfiletypescheck.get('nextSibling'); 
            var usqsupportedfiles = Y.one('#fgroup_id_assignsubmission_usqfiletypes_filetypes');
            var usqsupportedother = Y.one('#fitem_id_assignsubmission_usqfiletypes_filetypesother');

            var recordertype = Y.one('#fitem_id_assignsubmission_onlinepoodll_recordertype');
            var timelimit = Y.one('#fitem_id_assignsubmission_onlinepoodll_timelimit');
            var onlinepoodllcheck = Y.one('#id_assignsubmission_onlinepoodll_enabled');

            var wordlimit = Y.one('#fgroup_id_assignsubmission_onlinetext_wordlimit_group');
            var onlinetextcheck = Y.one('#id_assignsubmission_onlinetext_enabled');

            // Handle visibility of sub options when Media collection type checked.
            if (mediagallerycheck.get('checked')) {
                mediagallery.show();
            } else {
                mediagallery.hide();
            }
            mediagallerycheck.on('change', function (e) {
                mediagallery.toggleView();
            });

            // Handle visibility of sub options when File submissions type checked.
            if (filesubmitcheck.get('checked')) {
                maxfilesub.show();
                maxfilesize.show();
                usqfiletypescheck.show();
                usqfilelabel.show();
            } else {
                maxfilesub.hide();
                maxfilesize.hide();
                usqfiletypescheck.hide();
                usqfilelabel.hide();
            }
            filesubmitcheck.on('change', function (e) {
                maxfilesub.toggleView();
                maxfilesize.toggleView();
                usqfiletypescheck.toggleView();
                usqfilelabel.toggleView();
                // Force hide Accepted file type sub options when File submissions type was checked.
                if(usqfiletypescheck.getAttribute('hidden') === 'hidden') {
                    usqfiletypescheck.set('checked', false);
                    usqsupportedfiles.hide();
                    usqsupportedother.hide();
                }
            });

            // Handle visibility of sub options when Online Poodll type checked.
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

            // Handle visibility of sub options when Online Text type checked.
            if (onlinetextcheck.get('checked')) {
                wordlimit.show();
            } else {
                wordlimit.hide();
            }
            onlinetextcheck.on('change', function (e) {
                wordlimit.toggleView();
            });

            // Handle visibility of sub options when Accepted file type checked.
            if (usqfiletypescheck.get('checked')) {
                usqsupportedfiles.show();
                usqsupportedother.show();
            } else {
                usqsupportedfiles.hide();
                usqsupportedother.hide();
            }
            usqfiletypescheck.on('change', function (e) {
                usqsupportedfiles.toggleView();
                usqsupportedother.toggleView();
            });

        },
    };
}, '@VERSION@', {'requires':['node','event']});
