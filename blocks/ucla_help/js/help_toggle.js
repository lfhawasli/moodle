/**
 * jQuery script to generate the help button + sidebar toggle event.
 */

var sidebartoggle = function(e) {

    $('.main.help.sidebar').sidebar({
        onChange: function() {

            $('.gradebook-header-row').toggle();
            $('.gradebook-student-column').toggle();

            $(window).delay(300).queue(function(next) {
                $(this).scrollTop($(window).scrollTop() + 1);
                $('.gradebook-header-row').toggle();
                $('.gradebook-student-column').toggle();
                next();
            });
        }
    }).sidebar('toggle');
}

$('.sidebar-buttons').append('<button class="btn btn-default btn-sm help-toggle">Help</button>');
$('.sidebar-buttons .help-toggle').on('click', sidebartoggle );

$('.help.sidebar .help-toggle').on('click', sidebartoggle);