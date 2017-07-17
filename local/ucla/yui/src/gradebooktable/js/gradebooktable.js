/**
 * YUI script to create floating headers student name column.
 */

// Get local_ucla plugin.
M.local_ucla = M.local_ucla || {};

// Create a gradebook module.
M.local_ucla.gradebook = {
    // Resuable nodes.
    node_student_header_cell: {},
    node_student_cell: {},
    node_footer_row: {},

    // Init module.
    init: function() {
        // Set up some reusable nodes.
        this.node_student_header_cell = Y.one('#studentheader');
        // First student cell.
        this.node_student_cell = Y.one('#user-grades .user.cell');
        // Averages row.
        this.node_footer_row = Y.one('#user-grades .avg');

        // Check if there are any students.
        if (this.node_student_cell) {
            // Generate floating elements.
            this.float_user_column();
            this.float_assignment_header();
            this.float_user_header();

            // Check if 'averages' is allowed.
            if (this.node_footer_row) {
                this.float_assignment_footer();
                this.update_assignment_footer_position();
            }

            // Set floating element initial positions.
            this.update_assignment_header_position();
            this.update_user_column_position();

            // Register ourselves with sidebar to receive updates.
            M.block_ucla_help.sidebar.register_module(this);

            Y.all('#user-grades .overridden').setAttribute('aria-label', 'Overriden grade');

            // Use native DOM scroll & resize events instead of YUI synthetic event.
            window.onscroll = function() {
                M.local_ucla.gradebook.update_assignment_footer_position();
                M.local_ucla.gradebook.update_assignment_header_position();
                M.local_ucla.gradebook.update_user_column_position();
            };
            window.onresize = function() {
                M.local_ucla.gradebook.update_assignment_footer_position();
                M.local_ucla.gradebook.update_assignment_header_position();
                M.local_ucla.gradebook.update_user_column_position();

                // Resize headers & footers.
                // This is an expensive operation, not expected to happen often.
                var headers = Y.all('#gradebook-header-container .gradebook-header-cell');
                var resizedcells = Y.all('#user-grades .heading .cell');

                var headeroffsetleft = Y.one('#studentheader').getX();
                var newcontainerwidth = 0;
                resizedcells.each(function(cell, idx) {
                    var headercell = headers.item(idx);

                    newcontainerwidth += cell.get('offsetWidth');
                    var styles = {
                        width: cell.get('offsetWidth'),
                        left: cell.getX() - headeroffsetleft + 'px'
                    };
                    headercell.setStyles(styles);
                });

                var footers = Y.all('#gradebook-footer-container .gradebook-footer-cell');

                if (footers.size() !== 0) {
                    var resizedavgcells = Y.all('#user-grades .avg .cell');

                    resizedavgcells.each(function(cell, idx) {
                        var footercell = footers.item(idx);
                        var styles = {
                            width: cell.get('offsetWidth'),
                            left: cell.getX() - headeroffsetleft + 'px'
                        };
                        footercell.setStyles(styles);
                    });
                    Y.one('#gradebook-footer-container').setStyle('width', newcontainerwidth);
                }

                Y.one('#gradebook-header-container').setStyle('width', newcontainerwidth);

            };
        }

        // Remove loading screen. Need to do YUI synthetic event to trigger.
        // on all browsers.
        Y.on('domready', function() {
            Y.one('.gradebook-loading-screen').remove(true);
        });
    },
    float_user_column: function() {
        // Grab the user names column.
        var user_column = Y.all('#user-grades .user.cell');

        // Generate a floating table.
        var floating_user_column = Y.Node.create('<div aria-hidden="true" id="gradebook-user-container"></div>');
        var floating_user_column_height = 0;
        var user_column_offset = this.node_student_cell.getY();

        user_column.each(function(node) {

            // Create cloned node and container.
            // We'll absolutely position the container to each cell position,
            // this will guarantee that student cells are always aligned.
            var container_node = Y.Node.create('<div class="gradebook-user-cell"></div>');

            // Grab the username.
            var usernamenode = node.cloneNode(true);
            container_node.append(usernamenode.getHTML());
            usernamenode = null;

            container_node.setStyles({
                'height': node.get('offsetHeight') + 'px',
                'width': node.get('offsetWidth') + 'px',
                'position': 'absolute',
                'top': (node.getY() - user_column_offset) + 'px'
            });

            floating_user_column_height += node.get('offsetHeight');
            // Retrieve the corresponding row.
            var classes = node.ancestor().getAttribute('class').split(' ').join('.');
            // Attach highlight event.
            container_node.on('click', function() {
                Y.one('.' + classes).all('.grade').toggleClass('hmarked');
            });
            // Add the cloned nodes to our floating table.
            floating_user_column.appendChild(container_node);

        }, this);

        // Style the table.
        floating_user_column.setStyles({
            'position': 'absolute',
            'left': this.node_student_cell.getX() + 'px',
            'top': this.node_student_cell.getY() + 'px',
            'width': this.node_student_cell.get('offsetWidth'),
            'height' : floating_user_column_height + 'px',
            'background-color': '#f9f9f9'
        });

        Y.one('body').append(floating_user_column);
    },
    float_user_header: function() {

        // Float the 'user name' header cell.
        var floating_user_header_cell = Y.Node.create('<div aria-hidden="true" id="gradebook-user-header-container"></div>');

        // Clone the node.
        var cellnode = this.node_student_header_cell.cloneNode(true);
        // Append node contents.
        floating_user_header_cell.append(cellnode.getHTML());
        floating_user_header_cell.setStyles({
            'position': 'absolute',
            'left': this.node_student_cell.getX() + 'px',
            'top': this.node_student_header_cell.getY() + 'px',
            'width': '200px',
            'height': this.node_student_header_cell.get('offsetHeight') + 'px'
        });

        // Safe for collection.
        cellnode = null;

        Y.one('body').append(floating_user_header_cell);
    },
    float_assignment_header: function() {
        var grade_headers = Y.all('#user-grades tr.heading .cell');

        // Generate a floating headers.
        var floating_grade_headers = Y.Node.create('<div aria-hidden="true" id="gradebook-header-container"></div>');

        var floating_grade_headers_width = 0;
        var floating_grade_headers_height = 0;
        var grade_headers_offset = this.node_student_header_cell.getX();

        grade_headers.each(function(node) {

            // Get the target column to highlight. This is embedded in
            // the column cell #, but it's off by one, so need to adjust for that.
            var col = node.getAttribute('class');

            // Extract the column #.
            var search = /c[0-9]+/g;
            var match = search.exec(col);
            match = match[0].replace('c', '');

            // Offset.
            var target_col = parseInt(match, 10);
            ++target_col;

            var nodepos = node.getX();

            // We need to clone the node, otherwise we mutate original obj.
            var nodeclone = node.cloneNode(true);

            var newnode = Y.Node.create('<div class="gradebook-header-cell"></div>');
            newnode.append(nodeclone.getHTML());
            newnode.addClass(nodeclone.getAttribute('class'));
            nodeclone = null;

            newnode.setStyles({
                'width': node.get('offsetWidth') + 'px',
                'height': node.get('offsetHeight') + 'px',
                'position': 'absolute',
                'left': (nodepos - grade_headers_offset) + 'px'
            });

            // Sum up total width.
            floating_grade_headers_width += parseInt(node.get('offsetWidth'), 10);
            floating_grade_headers_height = node.get('offsetHeight');

            // Attach 'highlight column' event to new node.
            newnode.on('click', function() {
                Y.all('.cell.c' + target_col).toggleClass('vmarked');
            });

            // Append to floating table.
            floating_grade_headers.appendChild(newnode);
        }, this);

        // Position header table.
        floating_grade_headers.setStyles({
            'position': 'absolute',
            'top': this.node_student_header_cell.getY() + 'px',
            'left': this.node_student_header_cell.getX() + 'px',
            'width': floating_grade_headers_width + 'px',
            'height' : floating_grade_headers_height + 'px'
        });

        Y.one('body').append(floating_grade_headers);
    },
    float_assignment_footer: function() {

        // Generate the sticky footer row.
        // Grab the row.
        var footer_row = Y.all('#user-grades .lastrow .cell');
        // Create a container.
        var floating_grade_footers = Y.Node.create('<div aria-hidden="true" id="gradebook-footer-container"></div>');
        var floating_grade_footer_width = 0;
        var footer_row_offset = this.node_footer_row.getX();
        // Copy nodes.
        footer_row.each(function(node) {

            var nodepos = node.getX();
            var cellnodeclone = node.cloneNode(true);

            var newnode = Y.Node.create('<div class="gradebook-footer-cell"></div>');
            newnode.append(cellnodeclone.getHTML());
            newnode.setStyles({
                'width': node.get('offsetWidth') + 'px',
                'height': '50px',
                'position': 'absolute',
                'left': (nodepos - footer_row_offset) + 'px'
            });

            floating_grade_footers.append(newnode);
            floating_grade_footer_width += parseInt(node.get('offsetWidth'), 10);
        }, this);

        // Attach 'Update' button.
        var update_button = Y.one('#gradersubmit');
        if (update_button) {
            var button = Y.Node.create('<button class="btn btn-sm btn-default">' +
                    update_button.getAttribute('value') + '</button>');
            button.on('click', function() {
                update_button.simulate('click');
            });
            floating_grade_footers.one('.gradebook-footer-cell').append(button);
        }

        // Position the row.
        floating_grade_footers.setStyles({
            'position': 'absolute',
            'left': this.node_footer_row.getX() + 'px',
            'bottom': '0',
            'height' : '50px',
            'width' : floating_grade_footer_width + 'px'
        });

        Y.one('body').append(floating_grade_footers);
    },
    update_user_column_position: function() {
        var offsetcutoff = window.pageXOffset;
        var sidebar_active = Y.one('.sidebar.active');

        if (sidebar_active) {
            offsetcutoff = sidebar_active.get('offsetWidth') + window.pageXOffset;
        }

        var firstusercell = document.querySelectorAll("#user-grades .user.cell")[0];
        var firstusercellpos = firstusercell.offsetLeft + firstusercell.offsetParent.offsetLeft;

        var user_column = document.getElementById('gradebook-user-container');
        var user_column_header = document.getElementById('gradebook-user-header-container');

        if (offsetcutoff > firstusercellpos) {
            user_column.style.left = offsetcutoff + 'px';
            user_column_header.style.left = offsetcutoff + 'px';
        }

        if (offsetcutoff < firstusercellpos) {
            user_column.style.left = firstusercellpos + 'px';
            user_column_header.style.left = firstusercellpos + 'px';
        }
    },
    update_assignment_header_position: function() {
        // CCLE-4795 - New header is static and is exactly 40px tall.
        var static_header_offset = 40;

        var header = document.getElementById('gradebook-header-container');
        var header_cell = document.getElementById('studentheader');

        var user_column_header = document.getElementById('gradebook-user-header-container');

        header.style.left = header_cell.offsetLeft + header_cell.offsetParent.offsetLeft + 'px';

        var headercelltop = header_cell.offsetTop + header_cell.offsetParent.offsetTop;

        // Check that we're at offset.
        if (window.pageYOffset + static_header_offset > headercelltop ) {
            // Use new header height in offset calculation.
            header.style.top = window.pageYOffset + static_header_offset + 'px';
            user_column_header.style.top = window.pageYOffset + static_header_offset + 'px';
        } else {
            header.style.top = headercelltop + 'px';
            user_column_header.style.top = headercelltop + 'px';
        }

    },
    update_assignment_footer_position: function() {

        var lastrow = document.querySelectorAll('#user-grades .avg')[0];
        // Check that Average footer is available.
        if (lastrow === undefined) {
            return;
        }

        var footer = document.getElementById('gradebook-footer-container');
        var lastrowpos = lastrow.offsetTop + lastrow.offsetParent.offsetTop;

        var header_cell = document.getElementById('studentheader');
        footer.style.left = header_cell.offsetLeft + header_cell.offsetParent.offsetLeft + 'px';

        if (window.pageYOffset + window.innerHeight < lastrowpos) {
            footer.style.top = (window.pageYOffset + window.innerHeight - 50) + 'px';
            footer.classList.add('gradebook-footer-row-sticky');
        } else {
            footer.style.top = lastrowpos + 'px';
            footer.classList.remove('gradebook-footer-row-sticky');
        }
    },
    sidebar_toggle: function() {
        // Update positions when sidebar toggles.
        this.update_assignment_footer_position();
        this.update_assignment_header_position();
        this.update_user_column_position();
    },
    sidebar_toggle_pre: function() {
        if (this.node_footer_row) {
            Y.one('#gradebook-footer-container').hide();
        }
        Y.one('#gradebook-user-container').hide();
        Y.one('#gradebook-user-header-container').hide();
        Y.one('#gradebook-header-container').hide();
    },
    sidebar_toggle_post: function() {
        if (this.node_footer_row) {
            Y.one('#gradebook-footer-container').show();
        }
        Y.one('#gradebook-user-container').show();
        Y.one('#gradebook-user-header-container').show();
        Y.one('#gradebook-header-container').show();
    }
};
