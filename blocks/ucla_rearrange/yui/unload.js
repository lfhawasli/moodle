M.block_ucla_rearrange = M.block_ucla_rearrange || {};
M.block_ucla_rearrange.init = function(Y) {

    Y.on('domready', function() {

        try {
            // Get value of serialized data.
            var tableVal = [];
            var it = 0;
            $('*[id*="serialized-"]').each(function(e) {
                tableVal[it] = ($(this).val());
                it++;
            });
            tableVal.push ($("#serialized").val());
            M.block_ucla_rearrange.initialdata = tableVal;
            var warningmessage = M.util.get_string('changesmadereallygoaway', 'moodle');
            M.core_formchangechecker.report_form_dirty_state = function() {
                var tableTemp = [];
                it = 0;
                $('*[id*="serialized-"]').each(function(e) {
                    tableTemp[it] = ($(this).val());
                    it++;
                });
                tableTemp.push ($("#serialized").val());
                // Compare the init value with current value.
                for (var i = 0; i < tableTemp.length; i++) {
                    // If different, check if its just the order that's being changed.
                    if(tableTemp[i] != M.block_ucla_rearrange.initialdata[i]) {
                        var temp = tableTemp[i].split("&");
                        var init = M.block_ucla_rearrange.initialdata[i].split("&");
                        if (temp.length != init.length) {
                             return warningmessage;
                        } else {
                            // Get the id number of each element, sort then compare.
                            for (var j = 0; j < temp.length; j++) {
                                temp[j] = temp[j].split("=")[1];
                                init[j] = init[j].split("=")[1];
                            }
                            var index = temp.indexOf("0");
                            if (index > -1) {
                                temp.splice(index, 1);
                            }
                            index = init.indexOf("0");
                            if (index > -1) {
                                init.splice(index, 1);
                            }
                            for (var k = 0; k < temp.length; k++) {
                                if (temp[k] != init[k]) {
                                    return warningmessage;
                                }
                            }
                        }
                    }
                }
            }
            window.onbeforeunload = M.core_formchangechecker.report_form_dirty_state;

            // If the form is submitted, don't trigger onbeforeunload action.
            Y.one('#mform1').on('submit', function(e) {
                window.onbeforeunload = null
            })
        } catch (err) {
            // Ignore errors.  When you end up here, it means
            // the form has already been submitted and IDs are no longer in scope.
        }

        // CCLE-3930 - Rearrange erases modules in sections when javascript is turned off or not fully loaded.
        // Submission buttons are only enabled when required interface-1.2.min.js file is fully loaded.
        // Variable $.iNestedSortable exists when interface-1.2.min.js is loaded.
        if ($.iNestedSortable) {
            // Enable form submit - the rearrange form is disabled by default.
            Y.all('.mform input[type="submit"]').removeAttribute('disabled');
        } else {
            // Disable buttons because interface-1.2.min.js file is not loaded.
            $(window).load(function() {
                $('.mform input[type="submit"]').attr("disabled", true);
            });
        }
    });
}
