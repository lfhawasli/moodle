/**
 * On select copyright status store strings with file ids to an element
 *
 */

YUI().use('event-delegate', function(Y){
     Y.delegate('change', function (e){
     }, '#block_ucla_copyright_status_id_cp_list', 'select');
});


/**
 * On button click save changes to database
 *
 */

YUI().use('node-base', function(Y){
    var btnl_Click = function(e){
        $('#block_ucla_copyright_status_form_copyright_status_list').serialize();
    };
    Y.on('click', btnl_Click, '#block_ucla_copyright_status_btn1');
});

function uclaCopyrightTextExtraction(node) {
    node = $(node);
    listElements = node.find('li');
    if (listElements.length > 0) {
        node = listElements.first();
    }
    return node.text();
}

/**
 * On button click toggle checkboxes
 */
Y.one('#checkall').on('click', function() {
    Y.all('input.usercheckbox').each(function() {
        this.set('checked', 'checked');
    })
});

Y.one('#checknone').on('click', function() {
    Y.all('input.usercheckbox').each(function() {
        this.set('checked', '');
    })
});

