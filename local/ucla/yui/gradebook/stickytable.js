/**
 * YUI script to create floating headers student name column.
 */


YUI().use('node', 'event', function(Y) {

    Y.on('domready', function() {

        // Check that we're ont he grade report page
        var gradebook = Y.one('.path-grade-report');

        if (gradebook) {

            //
            // Generate sticky header and student column

            // Grab the user names column
            var user_column = Y.all('#user-grades tbody tr .user.cell');
            // Now generate a fixed position for the cell
            var user_cell = Y.one('#user-grades tbody tr .user.cell');
            var user_cell_position = user_cell.getXY();

            // Generate a floating table
            var user_table = Y.Node.create('<div role="presentation" class="gradebook-student-column"></div>');

            user_column.each(function(node) {

                // Create cloned node and container.
                // We'll absolutely position the container to each cell position,
                // this will guarantee that student cells are always aligned.
                var newnode = node.cloneNode(true);
                var container_node = Y.Node.create('<div class="gradebook-student-container"></div>');
                container_node.append(newnode);

                var nodepos = node.getXY();

                container_node.setStyles({
                    'height': node.get('offsetHeight') + 'px',
                    'width': node.get('offsetWidth') + 'px',
                    'overflow': 'hidden',
                    'position': 'absolute',
                    'top': (nodepos[1] - user_cell_position[1]) + 'px'
                });


                // Retrieve the corresponding row
                var classes = node.ancestor().getAttribute('class').split(' ').join('.');
                // Attach highlight event
                container_node.on('click', function(e) {
                    Y.one('.' + classes).all('.grade').toggleClass('hmarked');
                });
                // Add the cloned nodes to our floating table
                user_table.appendChild(container_node);
            });

            // Generate dimensions
            user_table.setStyles({
                'position': 'absolute',
                'left': user_cell_position[0] + 'px',
                'top': user_cell_position[1] + 'px',
                'width': user_cell.get('offsetWidth')
            });

            //
            // Grab the header row cells
            var grade_headers = Y.all('#user-grades tbody tr.heading .cell');

            // Generate a floating headers
            var header_table = Y.Node
                    .create('<table><tbody><tr></tr></tbody></table>')
                    .addClass('gradebook-header-row');

            var header_table_width = 0;
            var starting_position = Y.one('#user-grades #studentheader').getXY();

            grade_headers.each(function(node) {

                // Get the target column to highlight.  This is embedded in
                // the column cell #, but it's off by one, so need to adjust for that.
                var col = node.getAttribute('class');

                // Extract the column #
                var search = /c[0-9]+/g;
                var match = search.exec(col);
                match = match[0].replace('c', '');

                // Offset
                var target_col = parseInt(match)
                ++target_col;

                var nodepos = node.getX();
                
                // We need to clone the node, otherwise we mutate original obj
                var newnode = node.cloneNode(true);
                newnode.setStyles({
                    'width' : node.get('offsetWidth') + 'px',
                    'height' : node.get('offsetHeight') + 'px',
                    'position' : 'absolute',
                    'left' : (nodepos - starting_position[0]) + 'px'
                });

                // Sum up total width
                header_table_width += parseInt(node.get('offsetWidth'));
                
                // Attach highlight event to new node
                newnode.on('click', function(e) {
                    Y.all('.cell.c' + target_col).toggleClass('vmarked');
                });

                // Append to floating table    
                header_table.one('tr').appendChild(
                        newnode
                        );
            });


            // Position header table
            header_table.setStyles({
                'position' : 'absolute',
                'top' : starting_position[1] + 'px',
                'left' : starting_position[0] + 'px',
                'width' : 'auto'
            });

            // Render in document body
            Y.one('body').appendChild(user_table);
            Y.one('body').appendChild(header_table);

            // Attach scrolling event listener
            Y.on('scroll', function(e) {

                // User column
                var offsetCutoff = window.pageXOffset;
                var sidebar_active = Y.one('.sidebar.active');
                if (sidebar_active) {
                    offsetCutoff = sidebar_active.get('offsetWidth') + window.pageXOffset;
                }

                var table_position = Y.one('#user-grades tbody tr .user.cell').getXY()[0];

                if (offsetCutoff > table_position) {
                    user_table.setStyle('left', offsetCutoff + 'px');
                }

                if (offsetCutoff < table_position) {
                    user_table.setStyle('left', table_position + 'px');
                }

                // Header table

                // This offset will change when the sidebar is active.. keep it refreshed
                header_table.setStyle('left', Y.one('#user-grades #studentheader').getX() + 'px');

                if (window.pageYOffset > starting_position[1]) {
                    header_table.setStyle('top', window.pageYOffset + 'px');
                } else {
                    header_table.setStyle('top', starting_position[1] + 'px');
                }
            });

        }
    });

});

