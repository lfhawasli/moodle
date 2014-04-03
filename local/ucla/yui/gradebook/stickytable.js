/**
 * YUI script to create floating headers student name column.
 */


YUI().use('node', 'event', 'anim', 'moodle-block_ucla_help-doc_loader', function(Y) {

    // Get local_ucla plugin
    M.local_ucla = M.local_ucla || {};

    // Create a gradebook module
    M.local_ucla.gradebook = {
        
        // Floating elements
        floating_user_column: {},
        floating_user_header_cell: {},
        floating_grade_headers: {},
        floating_grade_footers: {},
        
        // Resuable nodes
        node_student_header_cell: {},
        node_student_cell: {},
        node_footer_row: {},
        
        // Node classes
        class_floating_user_column : 'gradebook-student-column',
        class_floating_user_header : 'gradebook-floating-header',
        class_floating_grade_header : 'gradebook-header-row',
        class_floating_footer_header : 'gradebook-footer-row',

        // Init module
        init: function() {

            // Set up some reusable nodes
            // 
            // Student column sort header
            this.node_student_header_cell = Y.one('#user-grades #studentheader');
            // First student cell
            this.node_student_cell = Y.one('#user-grades tbody tr .user.cell');
            // Footer row
            this.node_footer_row = Y.one('#user-grades .lastrow');

            // Generate floating headers
            this.float_user_column();
            this.float_assignment_header();
            this.float_assignment_footer();
            this.float_user_header();

            // Render floating elements in document body
            var docbody = Y.one('body');
            docbody.appendChild(this.floating_user_column);
            docbody.appendChild(this.floating_grade_headers);
            docbody.appendChild(this.floating_grade_footers);
            docbody.appendChild(this.floating_user_header_cell);

            // Set element positions
            this.update_assignment_footer_position();
            this.update_assignment_header_position();
            this.update_user_column_header_position();
            this.update_user_column_position();
            
            // Register ourselves to receive sidebar updates
            M.block_ucla_help.sidebar.register_module(this);
        },

        float_user_column: function() {
            // Grab the user names column
            var user_column = Y.all('#user-grades tbody tr .user.cell');

            // Generate a floating table
            this.floating_user_column = Y.Node.create('<div role="presentation" class="gradebook-student-column"></div>');

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
                    'top': (nodepos[1] - this.node_student_cell.getY()) + 'px'
                });


                // Retrieve the corresponding row
                var classes = node.ancestor().getAttribute('class').split(' ').join('.');
                // Attach highlight event
                container_node.on('click', function(e) {
                    Y.one('.' + classes).all('.grade').toggleClass('hmarked');
                });
                // Add the cloned nodes to our floating table
                this.floating_user_column.appendChild(container_node);

            }, this);

            // Style the table
            this.floating_user_column.setStyles({
                'position': 'absolute',
                'left': this.node_student_cell.getX() + 'px',
                'top': this.node_student_cell.getY() + 'px',
                'width': this.node_student_cell.get('offsetWidth'),
                'height': '100%',
                'background-color': '#f9f9f9'
            });
        },

        float_user_header: function() {
            // 
            // Float the 'names' cell
            //

            this.floating_user_header_cell = Y.Node.create('<div role="presentation" class="gradebook-floating-header"></div>');
            this.floating_user_header_cell.append(this.node_student_header_cell.cloneNode(true))
            this.floating_user_header_cell.setAttribute('colspan', '1');
            this.floating_user_header_cell.setStyles({
                'position': 'absolute',
                'left': this.node_student_cell.getX() + 'px',
                'top': this.node_student_header_cell.getY() + 'px',
                'width': (this.node_student_header_cell.get('offsetWidth') - 33) + 'px',
                'height': this.node_student_header_cell.get('offsetHeight') + 'px',
            });
        },

        float_assignment_header: function() {
            var grade_headers = Y.all('#user-grades tbody tr.heading .cell');

            // Generate a floating headers
            this.floating_grade_headers = Y.Node
                    .create('<table role="presentation"><tbody><tr></tr></tbody></table>')
                    .addClass('gradebook-header-row');

            var floating_grade_headers_width = 0;

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
                    'width': node.get('offsetWidth') + 'px',
                    'height': node.get('offsetHeight') + 'px',
                    'position': 'absolute',
                    'left': (nodepos - this.node_student_header_cell.getX()) + 'px'
                });

                // Sum up total width
                floating_grade_headers_width += parseInt(node.get('offsetWidth'));

                // Attach 'highlight column' event to new node
                newnode.on('click', function(e) {
                    Y.all('.cell.c' + target_col).toggleClass('vmarked');
                });

                // Append to floating table    
                this.floating_grade_headers.one('tr').appendChild(newnode);
            }, this);


            // Position header table
            this.floating_grade_headers.setStyles({
                'position': 'absolute',
                'top': this.node_student_header_cell.getY() + 'px',
                'left': this.node_student_header_cell.getX() + 'px',
                'width': 'auto'
            });
        },

        float_assignment_footer: function() {
            //
            // Generate the sticky footer row
            //

            // Grab the row
            var footer_row = Y.all('#user-grades .lastrow .cell');
            // Create a container
            this.floating_grade_footers = Y.Node.create('<div role="presentation" class="gradebook-footer-row"></div>');

            // Copy nodes
            footer_row.each(function(node) {

                var nodepos = node.getX();
                var newnode = node.cloneNode(true);
                newnode.removeClass('range');
                newnode.setStyles({
                    'width': node.get('offsetWidth') + 'px',
                    'height': 40 + 'px',
                    'position': 'absolute',
                    'left': (nodepos - this.node_footer_row.getX()) + 'px'
                });

                this.floating_grade_footers.append(newnode);
            }, this);

            // Position the row
            this.floating_grade_footers.setStyles({
                'position': 'absolute',
                'left': this.node_footer_row.getX() + 'px',
//                'top' : footer_row_position[1] + '0px'
                'bottom': '0'
            });
        },

        update_user_column_position: function() {
            var offsetCutoff = window.pageXOffset;
            var sidebar_active = Y.one('.sidebar.active');

            if (sidebar_active) {
                offsetCutoff = sidebar_active.get('offsetWidth') + window.pageXOffset;
            }

            var table_position = Y.one('#user-grades tbody tr .user.cell').getX();

            if (offsetCutoff > table_position) {
                this.floating_user_column.setStyle('left', offsetCutoff + 'px');
                this.floating_user_header_cell.setStyle('left', offsetCutoff + 'px');
                this.floating_user_column.addClass('gradebook-student-column-sticky');
            }

            if (offsetCutoff < table_position) {
                this.floating_user_column.setStyle('left', table_position + 'px');
                this.floating_user_header_cell.setStyle('left', table_position + 'px');
                this.floating_user_column.removeClass('gradebook-student-column-sticky');
            }
        },

        update_user_column_header_position: function() {
            //
            if (window.pageYOffset > this.node_student_header_cell.getY()) {
                this.floating_user_header_cell.setStyle('top', window.pageYOffset + 'px');
            } else {
                this.floating_user_header_cell.setStyle('top', this.node_student_header_cell.getY() + 'px');
            }
//            // 
//            if (window.pageYOffset > this.node_student_header_cell.getY()) {
//                this.floating_user_header_cell.setStyle('top', window.pageYOffset + 'px');
//            } else {
//                this.floating_user_header_cell.setStyle('top', this.node_student_header_cell.getY() + 'px');
//            }
        },

        update_assignment_header_position: function() {

            this.floating_grade_headers.setStyle('left', Y.one('#user-grades #studentheader').getX() + 'px');

            if (window.pageYOffset > this.node_student_header_cell.getY()) {
                this.floating_grade_headers.setStyle('top', window.pageYOffset + 'px');
                this.floating_grade_headers.addClass('gradebook-header-row-sticky');

            } else {
                this.floating_grade_headers.setStyle('top', this.node_student_header_cell.getY() + 'px');
                this.floating_grade_headers.removeClass('gradebook-header-row-sticky');
            }

        },

        update_assignment_footer_position: function() {

            this.floating_grade_footers.setStyle('left', Y.one('#user-grades #studentheader').getX() + 'px');
            
            if (window.pageYOffset + window.innerHeight < this.node_footer_row.getY()) {
                this.floating_grade_footers.setStyle('top', (window.pageYOffset + window.innerHeight - 40) + 'px');
                this.floating_grade_footers.addClass('gradebook-footer-row-sticky');
            } else {
                this.floating_grade_footers.setStyle('top', this.node_footer_row.getY() + 'px');
                this.floating_grade_footers.removeClass('gradebook-footer-row-sticky');

            }
        },
        
        sidebar_toggle : function(args) {
            
            // Update positions when sidebar toggles
            this.update_assignment_footer_position();
            this.update_assignment_header_position();
            this.update_user_column_header_position();
            this.update_user_column_position();
        },
        
        sidebar_toggle_pre : function(args) {
            
            this.floating_user_column.hide();
            this.floating_user_header_cell.hide();
            this.floating_grade_footers.hide();
            this.floating_grade_headers.hide();       
        },
        
        sidebar_toggle_post : function(args) {
            
            this.floating_user_column.show();
            this.floating_user_header_cell.show();
            this.floating_grade_footers.show();
            this.floating_grade_headers.show();
        }
    };


    // After DOM loads... 
    Y.on('domready', function() {

        // Check that we're ont he grade report page
        var gradebook = Y.one('.path-grade-report');

        if (gradebook) {
            //
            // Generate sticky rows and columns
            M.local_ucla.gradebook.init();

            // Attach resize event
            Y.on('resize', function(e) {
                M.local_ucla.gradebook.update_assignment_footer_position();
                M.local_ucla.gradebook.update_assignment_header_position();
                M.local_ucla.gradebook.update_user_column_header_position();
                M.local_ucla.gradebook.update_user_column_position();
            });

            // Attach scrolling event
            Y.on('scroll', function(e) {

                M.local_ucla.gradebook.update_assignment_footer_position();
                M.local_ucla.gradebook.update_assignment_header_position();
                M.local_ucla.gradebook.update_user_column_header_position();
                M.local_ucla.gradebook.update_user_column_position();

            });
            
            // Set ARIA labels for overriden grades
            Y.all('#user-grades .overridden').setAttribute('aria-label', 'Overriden grade');
        }
    });

});

