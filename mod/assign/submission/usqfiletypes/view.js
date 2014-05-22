/**
 * When accepted file type checkbox is checked, list accepted file type, otherwise, hide them
 *
**/

YUI().use('node', 'event', function (Y) {
    var node1 = Y.one('#fgroup_id_assignsubmission_usqfiletypes_filetypes');
    var node2 = Y.one('#fitem_id_assignsubmission_usqfiletypes_filetypesother');
    var check = Y.one('#id_assignsubmission_usqfiletypes_enabled');
    if (check.get('checked'))
    {
        node1.show();
        node2.show();
    } else {
        node1.hide();
        node2.hide();
    }
    check.on('change', function (e) {
       node1.toggleView();
	   node2.toggleView();
    });
});
