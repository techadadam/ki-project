document.addEventListener("DOMContentLoaded", () => {
  const menuButton = document.querySelector(".menu-toggle");
  const mobileMenu = document.querySelector(".mobile-navigation");

  if (!menuButton || !mobileMenu) return;

  function openMenu() {
    menuButton.classList.add("is-open");
    mobileMenu.classList.add("is-open");
    document.body.classList.add("menu-open");

    menuButton.setAttribute("aria-expanded", "true");
  }

  function closeMenu() {
    menuButton.classList.remove("is-open");
    mobileMenu.classList.remove("is-open");
    document.body.classList.remove("menu-open");

    menuButton.setAttribute("aria-expanded", "false");
  }

  menuButton.addEventListener("click", () => {
    const isOpen = menuButton.classList.contains("is-open");

    if (isOpen) {
      closeMenu();
    } else {
      openMenu();
    }
  });

  mobileMenu.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", closeMenu);
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeMenu();
    }
  });

  window.addEventListener("resize", () => {
    if (window.innerWidth > 1200) {
      closeMenu();
    }
  });
});
