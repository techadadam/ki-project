// EmailJS
(function () {
  emailjs.init("PsWwLTTIigEf4obNB"); // public key
})();

document
  .getElementById("contact-form")
  .addEventListener("submit", function (e) {
    e.preventDefault();

    emailjs.sendForm("service_nkzemk9", "template_hlanhl5", this).then(
      function (response) {
        alert("Formularz został przesłany! Dziękujemy.");
      },
      function (error) {
        alert("Coś poszło nie tak... ");
        console.error("Błąd:", error);
      },
    );

    this.reset();
  });
