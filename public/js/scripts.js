/*!
 * Start Bootstrap - SB Admin v7.0.7 (https://startbootstrap.com/template/sb-admin)
 * Copyright 2013-2023 Start Bootstrap
 * Licensed under MIT (https://github.com/StartBootstrap/startbootstrap-sb-admin/blob/master/LICENSE)
 */
//
// Scripts
//

window.addEventListener("DOMContentLoaded", (event) => {
  const sidebarToggle = document.body.querySelector("#sidebarToggle");

  function applySidebarState() {
    const isDesktop = window.innerWidth >= 992;

    if (isDesktop) {
      const stored = localStorage.getItem("sb|sidebar-toggle");
      document.body.classList.toggle("sb-sidenav-toggled", stored === "true");
    } else {
      document.body.classList.remove("sb-sidenav-toggled");
    }
  }

  applySidebarState();

  if (sidebarToggle) {
    sidebarToggle.addEventListener("click", (event) => {
      event.preventDefault();
      document.body.classList.toggle("sb-sidenav-toggled");

      if (window.innerWidth >= 992) {
        localStorage.setItem(
          "sb|sidebar-toggle",
          document.body.classList.contains("sb-sidenav-toggled"),
        );
      }
    });
  }

  const content = document.getElementById("layoutSidenav_content");
  if (content) {
    content.addEventListener("click", function () {
      if (
        window.innerWidth < 992 &&
        document.body.classList.contains("sb-sidenav-toggled")
      ) {
        document.body.classList.remove("sb-sidenav-toggled");
      }
    });
  }

  let resizeTimer;
  window.addEventListener("resize", () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(applySidebarState, 150);
  });
});
