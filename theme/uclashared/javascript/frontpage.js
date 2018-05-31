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
    $(".frontpage-alert-wrapper .alert-header-default").parent().css("border-left-color", "#88b851");
    $(".frontpage-alert-wrapper .alert-header-blue").parent().css("border-left-color", "#389998");
    $(".frontpage-alert-wrapper .alert-header-red").parent().css("border-left-color", "#d64f33");
    $(".frontpage-alert-wrapper .alert-header-yellow").parent().css("border-left-color", "#edb83d");
    $(".frontpage-alert-wrapper .box-text").append("<br/>");
});
