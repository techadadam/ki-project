// /stats/hit.js
(function () {
  try {
    var url = new URL("/stats/hit.php", location.origin);
    url.searchParams.set("p", location.pathname);
    url.searchParams.set("js", "1");

    if (navigator.sendBeacon) {
      navigator.sendBeacon(url.toString());
    } else {
      fetch(url.toString(), {
        method: "GET",
        cache: "no-store",
        keepalive: true,
      });
    }
  } catch (e) {}
})();
