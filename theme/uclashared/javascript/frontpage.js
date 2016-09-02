// Javascript to show and hide home page content up on hover on watermark.

$(document).ready(function(){
    $(".water-mark p").hover(function()
    {
        $("#page-content").fadeOut();
        $(".header-main").fadeOut();
        $("div.weeks-display").fadeOut();
    }, function()
    {
        $("#page-content").fadeIn();
        $(".header-main").fadeIn();
        $("div.weeks-display").fadeIn();
    });

});
