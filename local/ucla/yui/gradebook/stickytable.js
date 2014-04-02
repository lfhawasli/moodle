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
            var floating_user_column = Y.Node.create('<div role="presentation" class="gradebook-student-column"></div>');

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
                floating_user_column.appendChild(container_node);
            });

            // Generate dimensions
            floating_user_column.setStyles({
                'position': 'absolute',
                'left': user_cell_position[0] + 'px',
                'top': user_cell_position[1] + 'px',
                'width': user_cell.get('offsetWidth'),
                'height' : '100%',
                'background-color' : '#f9f9f9'
            });

            //
            // Grab the header row cells
            var grade_headers = Y.all('#user-grades tbody tr.heading .cell');

            // Generate a floating headers
            var floating_grade_headers = Y.Node
                    .create('<table><tbody><tr></tr></tbody></table>')
                    .addClass('gradebook-header-row');

            var floating_grade_headers_width = 0;
            
            var student_header_cell = Y.one('#user-grades #studentheader');
            var starting_position = student_header_cell.getXY();

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
                floating_grade_headers_width += parseInt(node.get('offsetWidth'));
                
                // Attach highlight event to new node
                newnode.on('click', function(e) {
                    Y.all('.cell.c' + target_col).toggleClass('vmarked');
                });

                // Append to floating table    
                floating_grade_headers.one('tr').appendChild(
                        newnode
                        );
            });


            // Position header table
            floating_grade_headers.setStyles({
                'position' : 'absolute',
                'top' : starting_position[1] + 'px',
                'left' : starting_position[0] + 'px',
                'width' : 'auto'
            });
            
            //
            // Generate the sticky footer row
            //

            // Grab the row
            var footer_row = Y.all('#user-grades .lastrow .cell');
            // Get row position
            var footer_row_position = Y.one('#user-grades .lastrow').getXY();
            // Create a container
            var floating_footer_row = Y.Node.create('<div class="gradebook-footer-row"></div>');
            
            // Copy nodes
            footer_row.each(function(node) {

                var nodepos = node.getX();
                var newnode = node.cloneNode(true);
                newnode.removeClass('range');
                newnode.setStyles({
                    'width': node.get('offsetWidth') + 'px',
                    'height': 40 + 'px',
                    'position': 'absolute',
                    'left': (nodepos - footer_row_position[0]) + 'px'
                });
               
               floating_footer_row.append(newnode);
            });
            
            // Position the row
            floating_footer_row.setStyles({
                'position': 'absolute',
                'left' : footer_row_position[0] + 'px',
//                'top' : footer_row_position[1] + '0px'
                'bottom' : '0'
            });
            
            // 
            // Float the 'names' cell
            //
            var floating_user_header_cell = Y.Node.create('<div class="gradebook-floating-header"></div>');
            floating_user_header_cell.append(student_header_cell.cloneNode(true))
            floating_user_header_cell.setAttribute('colspan', '1');
            floating_user_header_cell.setStyles({
                'position' : 'absolute',
                'left' : user_cell_position[0] + 'px',
                'top' : starting_position[1] + 'px',
                'width' : (student_header_cell.get('offsetWidth') - 33) + 'px',
                'height' : '40px',
            });

            // Render in document body
            Y.one('body').appendChild(floating_user_column);
            Y.one('body').appendChild(floating_grade_headers);
            Y.one('body').appendChild(floating_footer_row);
            Y.one('body').appendChild(floating_user_header_cell);
            
            if (window.pageYOffset + window.innerHeight < footer_row_position[1]) {
                floating_footer_row.setStyle('top', (window.innerHeight - 40) + 'px')
                floating_footer_row.addClass('gradebook-footer-row-sticky');
            }

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
                    floating_user_column.setStyle('left', offsetCutoff + 'px');
                    floating_user_header_cell.setStyle('left', offsetCutoff + 'px');
                    floating_user_column.addClass('gradebook-student-column-sticky');
                }

                if (offsetCutoff < table_position) {
                    floating_user_column.setStyle('left', table_position + 'px');
                    floating_user_header_cell.setStyle('left', table_position + 'px');
                    floating_user_column.removeClass('gradebook-student-column-sticky');
                }

                // Header table

                // This offset will change when the sidebar is active.. keep it refreshed
                floating_grade_headers.setStyle('left', Y.one('#user-grades #studentheader').getX() + 'px');

                if (window.pageYOffset > starting_position[1]) {
                    floating_grade_headers.setStyle('top', window.pageYOffset + 'px');
                    floating_user_header_cell.setStyle('top', window.pageYOffset + 'px');
                    floating_grade_headers.addClass('gradebook-header-row-sticky');
                    
                } else {
                    floating_grade_headers.setStyle('top', starting_position[1] + 'px');
                    floating_user_header_cell.setStyle('top', starting_position[1] + 'px');
                    floating_grade_headers.removeClass('gradebook-header-row-sticky');
                }
                
                if (window.pageYOffset + window.innerHeight < footer_row_position[1]) {
                    floating_footer_row.setStyle('top', (window.pageYOffset + window.innerHeight - 40) + 'px');
                    floating_footer_row.addClass('gradebook-footer-row-sticky');
                } else {
                    floating_footer_row.setStyle('top', footer_row_position[1] + 'px');
                    floating_footer_row.removeClass('gradebook-footer-row-sticky');

                }
            });

        }
    });

});

