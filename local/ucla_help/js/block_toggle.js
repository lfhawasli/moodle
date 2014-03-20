/**
 * jQuery script to toggle a sidebar containing a block.
 */

$('.sidebar-buttons .block-toggle').on('click', function(e) {
    $('.pre.block.sidebar').sidebar({
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