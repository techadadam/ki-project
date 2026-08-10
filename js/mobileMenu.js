const mobileButton = document.querySelector(".header__mobile-button");
const mobileMenu = document.querySelector(".mobile-menu");
const mobileLinks = document.querySelectorAll(".mobile-menu__link");

function toggleMobileMenu() {
  const menuIsOpen = mobileMenu.classList.toggle("is-open");

  mobileButton.classList.toggle("is-open", menuIsOpen);
  mobileButton.setAttribute("aria-expanded", menuIsOpen);

  document.body.classList.toggle("menu-open", menuIsOpen);
}

function closeMobileMenu() {
  mobileMenu.classList.remove("is-open");
  mobileButton.classList.remove("is-open");
  mobileButton.setAttribute("aria-expanded", "false");
  document.body.classList.remove("menu-open");
}

mobileButton.addEventListener("click", toggleMobileMenu);

mobileLinks.forEach((link) => {
  link.addEventListener("click", closeMobileMenu);
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    closeMobileMenu();
  }
});
