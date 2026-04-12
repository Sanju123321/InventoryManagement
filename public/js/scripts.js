/*!
 * Start Bootstrap - SB Admin v7.0.7 (https://startbootstrap.com/template/sb-admin)
 * Copyright 2013-2023 Start Bootstrap
 * Licensed under MIT (https://github.com/StartBootstrap/startbootstrap-sb-admin/blob/master/LICENSE)
 */
//
// Scripts
//

window.addEventListener("DOMContentLoaded", (event) => {
  // Toggle the side navigation
  const sidebarToggle = document.body.querySelector("#sidebarToggle");
  if (sidebarToggle) {
    sidebarToggle.addEventListener("click", (event) => {
      event.preventDefault();
      document.body.classList.toggle("sb-sidenav-toggled");
      localStorage.setItem(
        "sb|sidebar-toggle",
        document.body.classList.contains("sb-sidenav-toggled"),
      );
    });
  }

  // Close sidebar when tapping the overlay on mobile/tablet
  const content = document.getElementById("layoutSidenav_content");
  if (content) {
    content.addEventListener("click", function (e) {
      if (
        window.innerWidth < 992 &&
        document.body.classList.contains("sb-sidenav-toggled")
      ) {
        // Only close if clicking the overlay (::before pseudo-element area)
        document.body.classList.remove("sb-sidenav-toggled");
      }
    });
  }
});
