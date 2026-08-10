let sectionIsVisible = false;
const section = document.querySelector("#hero");

const observer = new IntersectionObserver(
  (entries, observer) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const foundElements = document.querySelectorAll(
          ".hero__subtitles-gallery .hero__subtitle",
        );
        let index = 0;

        const interval = setInterval(() => {
          if (index < foundElements.length - 1) {
            foundElements[index].classList.remove("reveal--subtitle");
            foundElements[index + 1].classList.add("reveal--subtitle");
            index = index + 1;
          } else {
            clearInterval(interval);

            sectionIsVisible = false;
          }
        }, 1500);
        observer.unobserve(section); // przestaje obserwować po pierwszym razie
      }
    });
  },
  { threshold: 0.5 },
); // 0.5 = połowa sekcji w widoku
observer.observe(section);
