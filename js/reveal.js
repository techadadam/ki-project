// reveal.js — reveal on scroll (IntersectionObserver)
// Użycie: dodaj klasę "reveal" do elementu w HTML, a na stronie dodaj <script src="reveal.js" defer></script>

(() => {
  // Jeśli na stronie nie ma żadnych elementów do reveal, nic nie rób
  const elements = document.querySelectorAll(".reveal");
  if (!elements.length) return;

  // Szanuj ustawienie systemowe "reduce motion"
  const prefersReducedMotion = window.matchMedia(
    "(prefers-reduced-motion: reduce)",
  ).matches;

  // Jeśli użytkownik nie chce animacji, po prostu pokaż elementy
  if (prefersReducedMotion) {
    elements.forEach((el) => el.classList.add("reveal--active"));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("reveal--active");
          observer.unobserve(entry.target); // animuj tylko raz
        }
      });
    },
    { threshold: 0.3 },
  );

  elements.forEach((el) => observer.observe(el));
})();
