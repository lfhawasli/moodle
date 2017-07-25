M.tool_uclacourserequestor = {
    init: function (Y) {

        // Workaround to override default Moodle table row styling.
        Y.one('body').hide();

        Y.on('domready', function () {
            var rows = Y.all('#uclacourserequestor_requests tr');

            rows.each(function (row) {
                row.get('children').each(function (node) {
                    rowClass = row.getAttribute('class').split(" ");
                    node.addClass(rowClass[0]);
                });
            });
            Y.one('body').show();

            // All event listener to all checkboxes to toggle row highlight.
            var allCheckboxes = Y.all('#uclacourserequestor_requests .lastcol input[type="checkbox"]');

            allCheckboxes.on('change', function (e) {
                var checkbox = e.target;

                if (checkbox.get('checked')) {
                    checkbox.ancestor('tr').addClass('checkbox-checked');
                } else {
                    checkbox.ancestor('tr').removeClass('checkbox-checked');
                }
            });

            // Style the already selected checkboxes.
            Y.all('#uclacourserequestor_requests .lastcol input[type="checkbox"]:checked').each(function(node) {
                node.ancestor('tr').addClass('checkbox-checked');
            })
        });

        // Attach 'check-all' event to instructors.
        var checkallinstructors = Y.one('#ucrgeneraloptions .check-all-instructors');
        if (checkallinstructors) {
            checkallinstructors.on('change', function(e) {
                var intructorCheckboxes = Y.all('#uclacourserequestor_requests td:nth-last-child(2):not(.warning):not(.error) input[type="checkbox"]');

                intructorCheckboxes.each(function(node) {
                    node.set('checked', e.target.get('checked'));
                });
            })
        }

        // Attach 'check all' event to ugrad, grad and tut.
        Y.all('#ucrgeneraloptions .label input[type="checkbox"]').on('change', function (e) {

            // Get the type: ugrad, grad, tut.
            var type = e.target.ancestor('span').getAttribute('class');
            type = type.replace(/label /g, "");

            // Select all checboxes of type.
            var myTypeCheckboxes = Y.all('#uclacourserequestor_requests td.lastcol.' + type + ' input[type="checkbox"]');

            // If user checked, then 'check' all checkboxes of type.
            if (e.target.get('checked')) {

                myTypeCheckboxes.each(function (node) {
                    node.set('checked', true);
                    node.ancestor('tr').addClass('checkbox-checked');
                });
            } else {
                // Else uncheck.
                myTypeCheckboxes.each(function (node) {
                    node.set('checked', false);
                    node.ancestor('tr').removeClass('checkbox-checked');
                });
            }
        });

        // Refresh the page on changing selected term.
        //
        // We do this by adding a "term=" query string param to the URL, and
        // then refreshing the page.
        //
        Y.all('#id_requestgroup_term').on('change', function (e) {
            var index = e.target.get('selectedIndex');
            var term = e.target.get('options').item(index).getAttribute('value');
            var url = window.location.href;

            // Check to see if the URL has the '?' necessary
            // to include params. If so, we append a '?' and parse
            // the URL appropriately.
            if (url.indexOf('?') > -1) {
                // Check to see if term param exists already or not. Then
                // parse the URL appropriately.
                if (url.indexOf("term=") > -1) {
                    var indexTerm = url.indexOf("term=") + 5;
                    url = url.substr(0, indexTerm) + term + url.substr(indexTerm + 3);
                } else {
                    url += "&term=" + term;
                }
            } else {
                url += "?term=" + term;
            }
            window.location = url;
        });
    }
};
