M.tool_uclacourserequestor = {
    init : function(Y) {
        
        // Workaround to override default Moodle table row styling.
        Y.one('body').hide();

        Y.on('domready', function () {
            rows = Y.all('#uclacourserequestor_requests tr');

            rows.each(function(row) {
                row.get('children').each(function(node) {
                    rowClass = row.getAttribute('class').split(" ");
                    node.addClass(rowClass[0]);
                });
            });
            Y.one('body').show();
        }); 

        // Helper function to get all valid checkboxes with a given class.
        function getCheckboxes(classType) {
            return Y.all("input." + classType).filter( function(checkbox) {
                            return !checkbox.getAttribute('disabled');
                         });
        }

        // Handle "Check All" checkboxes.
        checkAll = Y.all('.check-all');

        checkAll.on('change', function(e) {
            var classType = e.currentTarget.get('value');
            var checkboxes = getCheckboxes(classType);

            if(e.currentTarget.get('checked')) {
                checkboxes.set('checked', true);
            } else {
                checkboxes.set('checked', false);
            }
        });

        // Handle individual checkboxes.

        // Select checkboxes from last two columns (email instructor and to be built).
        numCols = document.getElementById('uclacourserequestor_requests').rows[0].cells.length;
        inputs = Y.all('.c' + (numCols-1) + ' input, .c' + (numCols-2) + ' input');

        // Check for change in any individual checkbox.
        inputs.on('change', function(e) {
            var classType = e.currentTarget.get('className');
            var checkboxes = getCheckboxes(classType)

            // Update corresponding "Check All" checkbox.
            checkAll.each(function(node) {
                if(node.get('value') === classType) {
                    // Select if all individual inputs selected.
                    if(e.currentTarget.get('checked')) {
                        var emptyCheckbox = checkboxes.some(function(checkbox) {
                            return !checkbox.get('checked');
                        });
                        if(!emptyCheckbox) {
                            node.set('checked', true);
                        }
                    } else {
                        // Unselect if individual input unselected.
                        node.set('checked', false);
                    }
                }
            });
        });
    }
};