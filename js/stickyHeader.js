// Sticky navigation

const sectionHeroEl = document.querySelector(".section__hero");

const stickyOnPoint = -100; //px
const stickyOffPoint = 50; //px

let ticking = false;

function updateStickyNavigation() {
  const heroBottom = sectionHeroEl.getBoundingClientRect().bottom;
  const isSticky = document.body.classList.contains("sticky");

  // Włączenie sticky podczas przewijania w dół
  if (!isSticky && heroBottom <= stickyOnPoint) {
    document.body.classList.add("sticky");
  }

  // Wyłączenie sticky podczas przewijania w górę
  if (isSticky && heroBottom >= stickyOffPoint) {
    document.body.classList.remove("sticky");
  }

  ticking = false;
}

window.addEventListener(
  "scroll",
  function () {
    if (!ticking) {
      window.requestAnimationFrame(updateStickyNavigation);
      ticking = true;
    }
  },
  { passive: true },
);

window.addEventListener("resize", updateStickyNavigation);

updateStickyNavigation();
