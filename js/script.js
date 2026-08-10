//console.log("Starting displaying WebSite!");

// current year
//const yearEl = document.querySelector(".year");
//const currentYear = new Date().getFullYear();
//yearEl.textContent = currentYear;

// Mobile navigation
/*
const btnNavEl = document.querySelector(".btn-mobile-nav");
const headerEl = document.querySelector(".header");

btnNavEl.addEventListener("click", function () {
  headerEl.classList.toggle("nav-open");
});
*/
// Smooth scroll

// Smooth scroll TYLKO dla #anchor
const allLinks = document.querySelectorAll("a[href]");

allLinks.forEach((link) => {
  link.addEventListener("click", (e) => {
    const href = link.getAttribute("href");

    // Działamy tylko dla # i #sekcja
    if (href === "#" || (href && href.startsWith("#"))) {
      e.preventDefault();

      // Scroll back to top
      if (href === "#") {
        window.scrollTo({ top: 0, behavior: "smooth" });
        return;
      }

      // Scroll to links
      const sectionEl = document.querySelector(href);
      if (sectionEl) sectionEl.scrollIntoView({ behavior: "smooth" });
    }

    // Zamknięcie nawigacji mobilnej (opcjonalnie)
    if (
      link.classList.contains("main-nav-link") &&
      typeof headerEl !== "undefined"
    ) {
      headerEl.classList.remove("nav-open");
    }
  });
});

// Flexbox gap Sa
function checkFlexGap() {
  var flex = document.createElement("div");
  flex.style.display = "flex";
  flex.style.flexDirection = "column";
  flex.style.rowGap = "1px";

  flex.appendChild(document.createElement("div"));
  flex.appendChild(document.createElement("div"));

  document.body.appendChild(flex);
  var isSupported = flex.scrollHeight === 1;
  flex.parentNode.removeChild(flex);
  //console.log(isSupported);

  if (!isSupported) document.body.classList.add("no-flexbox-gap");
}
checkFlexGap();
