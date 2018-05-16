function changeHamburger(x) {
  document.getElementById('hamburger-icon').classList.toggle("change-hamburger-icon");
}

$(document).ready(function() {
  if($("#hamburger-wrapper").length != 0) {
    let state = $("#hamburger-wrapper").attr("aria-expanded");
    if(state == "true") $("#hamburger-icon").addClass("change-hamburger-icon");
    else if(state == "false") $("#hamburger-icon").removeClass("change-hamburger-icon");
  }
});
