// Expand/Collapse Notifications under each course.
$('body').off().on("click", '#expand_course', (function() {
    var class_div = $(this).next(".course_div");
    class_div.find('img[src$="/expanded"]').click();
    class_div.toggle(400);
}));

// Expand/Collapse all Notifications in each category.
var expand1 = true;
$('body').on("click", '.class_course_expand', (function() {
    var src = this.src;
    var class_div = $(this).parent().parent().siblings(".class_sites_div");
    if (expand1) {
        class_div.find('img[src$="/expanded"]').click();
        class_div.find(".course_div").hide(400, function() {
            var newSrc = src.substring(0, (src.lastIndexOf("/")));
            newSrc += "/collapsed.svg";
            $('body').find('.class_course_expand').attr("src", newSrc);
        });
        expand1 = false;
    } else {
        class_div.find(".course_div").show(400, function() {
            var newSrc = src.substring(0, (src.lastIndexOf("/")));
            newSrc += "/expanded.svg";
            $('body').find('.class_course_expand').attr("src", newSrc);
        });
        expand1 = true;
    }
}));

// Expand/Collapse all Notifications in Collab Sites Category.
var expand2 = true;
$('body').on("click", '.collab_course_expand', (function() {
    var src = this.src;
    var class_div = $(this).parent().parent().siblings(".collab_sites_div");
    if (expand2) {
        class_div.find('img[src$="/expanded"]').click();
        class_div.find(".course_div").hide(400, function() {
            var newSrc = src.substring(0, (src.lastIndexOf("/")));
            newSrc += "/collapsed.svg";
            $('body').find('.collab_course_expand').attr("src", newSrc);
        });
        expand2 = false;
    } else {
        class_div.find(".course_div").show(400, function() {
            var newSrc = src.substring(0, (src.lastIndexOf("/")));
            newSrc += "/expanded.svg";
            $('body').find('.collab_course_expand').attr("src", newSrc);
        });
        expand2 = true;
    }
}));
