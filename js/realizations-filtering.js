const filterButtons = document.querySelectorAll(".realizacje-filter");
const locationSelect = document.querySelector(".realizacje-location");
const cards = document.querySelectorAll(".realizacja-card");
const emptyMessage = document.querySelector(".realizacje-empty");

let activeCategory = "all";

function filterRealizacje() {
  const activeLocation = locationSelect ? locationSelect.value : "all";
  let visibleCards = 0;

  cards.forEach((card) => {
    const categoryMatches =
      activeCategory === "all" || card.dataset.category === activeCategory;

    const locationMatches =
      activeLocation === "all" || card.dataset.location === activeLocation;

    const visible = categoryMatches && locationMatches;

    card.hidden = !visible;

    if (visible) {
      visibleCards++;
    }
  });

  if (emptyMessage) {
    emptyMessage.hidden = visibleCards !== 0;
  }
}

filterButtons.forEach((button) => {
  button.addEventListener("click", () => {
    filterButtons.forEach((item) => {
      item.classList.remove("active");
    });

    button.classList.add("active");
    activeCategory = button.dataset.filter;

    filterRealizacje();
  });
});

if (locationSelect) {
  locationSelect.addEventListener("change", filterRealizacje);
}
