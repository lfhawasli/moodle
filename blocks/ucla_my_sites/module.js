// Alerts are originally expanded.
var expand = true;
$('body').on("click", '#expand_course', (function() {
    var class_div = $(this).parent().next(".course_div");
    class_div.find('img[src$="/expanded"]').click();
    class_div.toggle(400, function() {});
}));