/**
 * jQuery script to toggle a sidebar containing a block.
 */

$('.sidebar-buttons .block-toggle').on('click', function(e) {
    $('.pre.block.sidebar').sidebar({
        onChange: function() {

            M.block_ucla_help.sidebar.invoke_function('sidebar_toggle_pre', '');

            $(window).delay(300).queue(function(next) {
                M.block_ucla_help.sidebar.invoke_function('sidebar_toggle', '');
                M.block_ucla_help.sidebar.invoke_function('sidebar_toggle_post', '');
                next();
            });
        }
    }).sidebar('toggle');
});