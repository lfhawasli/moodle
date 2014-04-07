/**
 * jQuery script to generate the help button + sidebar toggle event.
 */

var sidebartoggle = function(e) {

    $('.main.help.sidebar').sidebar({
        onChange: function() {
        
            M.block_ucla_help.sidebar.invoke_function('sidebar_toggle_pre', '');

            $(window).delay(300).queue(function(next) {
                M.block_ucla_help.sidebar.invoke_function('sidebar_toggle', '');
                M.block_ucla_help.sidebar.invoke_function('sidebar_toggle_post', '');
                next();
            });
        }
    }).sidebar('toggle');
};

$('.sidebar-buttons').append('<button class="btn btn-default btn-sm help-toggle">Help/Legend</button>');
$('.sidebar-buttons .help-toggle').on('click', sidebartoggle );

$('.help.sidebar .help-toggle').on('click', sidebartoggle);