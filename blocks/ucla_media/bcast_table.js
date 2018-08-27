// Collapses bruincast table rows by weeks.
$(function(){
    $(".fa-chevron-up").click(function(){
        var num = $(this).closest(".week-row").attr('num');

        $(this).toggleClass("open").closest(".week-row").nextAll(".fold:lt(" + num + ")").toggleClass("open");
        // Change the arrows based on whether the content for the row is shown.
        if ($(this).hasClass("open")) {
            $(this).removeClass("fa-chevron-up").addClass("fa-chevron-down");
        } else {
            $(this).removeClass("fa-chevron-down").addClass("fa-chevron-up");
        }
    });
}); 