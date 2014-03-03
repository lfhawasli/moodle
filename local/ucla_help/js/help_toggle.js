/**
 * 
 */

$('.help .help-toggle').sidebar({exclusive: true}).on('click', function(e) {

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
});