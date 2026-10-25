/* CCFM gallery admin — drag-to-reorder tiles, persisted via /admin/reorder.php. */
(function () {
  "use strict";
  var list = document.getElementById("tile-list");
  if (!list) return;

  var csrf = list.getAttribute("data-csrf") || "";
  var dragEl = null;

  function tileFromEvent(ev) {
    return ev.target.closest(".tile");
  }

  list.addEventListener("dragstart", function (ev) {
    var tile = tileFromEvent(ev);
    if (!tile) return;
    dragEl = tile;
    tile.classList.add("is-dragging");
    ev.dataTransfer.effectAllowed = "move";
    try { ev.dataTransfer.setData("text/plain", tile.dataset.id); } catch (e) {}
  });

  list.addEventListener("dragend", function () {
    if (dragEl) dragEl.classList.remove("is-dragging");
    dragEl = null;
    persist();
  });

  list.addEventListener("dragover", function (ev) {
    ev.preventDefault();
    if (!dragEl) return;
    var over = tileFromEvent(ev);
    if (!over || over === dragEl) return;
    var rect = over.getBoundingClientRect();
    var after = (ev.clientY - rect.top) > rect.height / 2;
    list.insertBefore(dragEl, after ? over.nextSibling : over);
  });

  var saveTimer = null;
  function persist() {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(function () {
      var ids = Array.prototype.map.call(list.querySelectorAll(".tile"), function (t) { return t.dataset.id; });
      var body = new URLSearchParams();
      body.set("csrf", csrf);
      ids.forEach(function (id) { body.append("ids[]", id); });
      fetch("/admin/reorder.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: body.toString(),
        credentials: "same-origin"
      }).then(function (r) { return r.json(); })
        .then(function (j) { flash(j && j.ok ? "Order saved" : "Could not save order", !(j && j.ok)); })
        .catch(function () { flash("Could not save order", true); });
    }, 400);
  }

  function flash(msg, isErr) {
    var el = document.getElementById("reorder-flash");
    if (!el) {
      el = document.createElement("div");
      el.id = "reorder-flash";
      el.className = "reorder-flash";
      document.body.appendChild(el);
    }
    el.textContent = msg;
    el.classList.toggle("is-err", !!isErr);
    el.classList.add("show");
    clearTimeout(flash._t);
    flash._t = setTimeout(function () { el.classList.remove("show"); }, 1800);
  }
})();
