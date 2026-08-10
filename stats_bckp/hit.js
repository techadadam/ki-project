(function () {
  // NIE LICZ testSafari001.html
  var path = (location.pathname || "").toLowerCase();

  // Wyślij ścieżkę strony do PHP
  var url = "/stats/hit.php?p=" + encodeURIComponent(location.pathname);

  fetch(url, { cache: "no-store", keepalive: true }).catch(function () {});
})();
