// For the Bruincast report display.
// Clicking the arrow next to a row in the Bruincast report table expands
// it to display all Bruincast content for that course. Upon clicking it 
// again, the table hides the content.
$(function(){
    $(".fa-chevron-down").click(function(){
        $(this).toggleClass("open").closest(".collapse-row").next(".fold").toggleClass("open");
        
        // Change the arrows based on whether the content for the row is shown.
        if ($(this).hasClass("open")) {
            $(this).removeClass("fa-chevron-down").addClass("fa-chevron-up");
        } else {
            $(this).removeClass("fa-chevron-up").addClass("fa-chevron-down");
        }
    });
});