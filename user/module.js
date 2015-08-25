
M.core_user = {};

M.core_user.init_participation = function(Y) {
	Y.on('change', function() {
		var action = Y.one('#formactionid');
		if (action.get('value') == '') {
			return;
		}
        var ok = false;
        Y.all('input.usercheckbox').each(function() {
            if (this.get('checked')) {
                ok = true;
            }
        });
        if (!ok) {
            // no checkbox selected

            // START UCLA MOD: CCLE-5316
            alertstring = M.util.get_string('noselectedusers', 'local_ucla');

            var alert = new M.core.alert({
                message: alertstring
            });

            /* Set the dropdown back to "Choose...", otherwise, after you select and come back to the
             * dropdown, it'll do nothing if you don't actually change the option.
             */
            action.set('value', '');

            return;
        }
        Y.one('#participantsform').submit();
	}, '#formactionid');

    Y.on('click', function(e) {
        Y.all('input.usercheckbox').each(function() {
            this.set('checked', 'checked');
        });
    }, '#checkall');

    Y.on('click', function(e) {
        Y.all('input.usercheckbox').each(function() {
            this.set('checked', '');
        });
    }, '#checknone');
};

M.core_user.init_tree = function(Y, expand_all, htmlid) {
    Y.use('yui2-treeview', function(Y) {
        var tree = new Y.YUI2.widget.TreeView(htmlid);

        tree.subscribe("clickEvent", function(node, event) {
            // we want normal clicking which redirects to url
            return false;
        });

        if (expand_all) {
            tree.expandAll();
        }

        tree.render();
    });
};
