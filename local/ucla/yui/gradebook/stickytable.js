/**
 * YUI script to create floating headers student name column.
 */


YUI().use('node', 'event', function(Y) {

    Y.on('domready', function() {
                
        // Check that we're ont he grade report page
        var gradebook = Y.one('.path-grade-report');
        
        if (gradebook) {
            
            // Grab the user names column
            var user_column = Y.all('#user-grades tbody tr .user.cell');
            
            // Generate a floating table
            var user_table = Y.Node.create('<div role="presentation"></div>').
                    addClass('gradebook-student-column');

            user_column.each(function(node) {
                
                // Create cloned node
                var newnode = node.cloneNode(true);
                newnode.setStyle('height', node.get('offsetHeight') + 'px')
                        .setStyle('maxHeight', node.get('offsetHeight') + 'px')
                        .setStyle('width', node.get('offsetWidth') + 'px')
                        .setStyle('overflow', 'hidden').setStyle('float', 'left')
                
                // Retrieve the corresponding row
                var classes = node.ancestor().getAttribute('class').split(' ').join('.');
                // Attach highlight event
                newnode.on('click', function(e) {
                        Y.one('.' + classes).all('.grade').toggleClass('hmarked');
                    });
                // Add the cloned nodes to our floating table
                user_table
                    
                    .appendChild(newnode);
            });
            
            // Now generate a fixed position for the cell
            var user_cell = Y.one('#user-grades tbody tr .user.cell');
            var position = user_cell.getXY();
            
            // Generate dimensions
            user_table.setStyle('position', 'absolute')
                    .setStyle('left', position[0] + 'px')
                    .setStyle('top', position[1] + 'px')
                    .setStyle('width', user_cell.get('offsetWidth'));
            
            
            //
            // Grab the header row cells
            var grade_headers = Y.all('#user-grades tbody tr.heading .catlevel1');
            
            // Generate a floating headers
            var header_table = Y.Node
                    .create('<table><tbody><tr></tr></tbody></table>')
                    .addClass('gradebook-header-row');
            
            grade_headers.each(function(node) {

                // Get the target column to highlight.  This is embedded in
                // the column cell #, but it's off by one, so need to adjust for that.
                var col = node.getAttribute('class');//.split(' ')[3].replace('c', '');

                // Extract the column #
                var search = /c[0-9]+/g;
                var match = search.exec(col);
                match = match[0].replace('c', '');
                
                // Offset
                var target_col = parseInt(match)
                ++target_col;
                
                // We need to clone the node, otherwise we mutate original obj
                var newnode = node.cloneNode(true)
                        .setStyle('width', node.get('offsetWidth'))
                        .setStyle('height', node.get('offsetHeight'));
                
                // Attach highlight event to new node
                newnode.on('click', function(e) {
                        Y.all('.cell.c' + target_col).toggleClass('vmarked');
                    });
                    
                // Append to floating table    
                header_table.one('tr').appendChild(
                    newnode
                );
            });
            
            var starting_position = Y.one('#user-grades tbody tr.heading .catlevel1').getXY();
            
            // Position header table
            header_table.setStyle('position', 'absolute');
            header_table.setStyle('top', starting_position[1] + 'px');
            header_table.setStyle('left', starting_position[0] + 'px');
            
            // Render in document body
            Y.one('body').appendChild(header_table);
            Y.one('body').appendChild(user_table);

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
                header_table.setStyle('left', Y.one('#user-grades tbody tr.heading .catlevel1').getXY()[0] + 'px');
                
                if (window.pageYOffset > starting_position[1]) {
                    header_table.setStyle('top', window.pageYOffset + 'px');
                } else {
                    header_table.setStyle('top', starting_position[1] + 'px');
                }
            });

        }
    });
    
});

