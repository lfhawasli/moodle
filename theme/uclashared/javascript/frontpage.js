// Javascript to show and hide home page content up on hover on watermark.

$(document).ready(function(){
    $("#image-credits").hover(function()
    {
        $("#greeting-wrapper").fadeOut();
        $("#sidebar").fadeOut();
        $("#header").fadeOut();
    }, function()
    {
        $("#greeting-wrapper").fadeIn();
        $("#sidebar").fadeIn();
        $("#header").fadeIn();
    });

});
