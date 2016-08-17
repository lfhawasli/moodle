// Expand/Collapse Notifications under each course.
$('body').on("click", '#expand_course', (function() {
    var class_div = $(this).parent().next(".course_div");
    class_div.find('img[src$="/expanded"]').click();
    class_div.toggle(400);
}));

// Expand/Collapse all Notifications in each category.
var expand = true;
$('body').on("click", '.course_expand', (function() {
    var src = this.src;
    var newSrc = src.substring(0, (src.lastIndexOf("/")));
    var class_div = $(this).parent().siblings().next(".sites_div");
    if (expand) {
        class_div.find('img[src$="/expanded"]').click();
        class_div.find(".course_div").hide(400, function() {
            newSrc += "/collapsed.svg";
            $('body').find('.course_expand').attr("src", newSrc);
        });
        expand = false;
    } else {
        class_div.find(".course_div").show(400, function() {
            newSrc += "/expanded.svg";
            $('body').find('.course_expand').attr("src", newSrc);
        });
        expand = true;
    }
}));
