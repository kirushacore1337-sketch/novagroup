/* tabs.js — переключатель «Хочу обучаться / Хочу обучать» (мышь и стрелки клавиатуры) */
(function () {
  "use strict";
  const { $, $$ } = App;
  const tabs = $$(".tab");
  if (!tabs.length) return;

  function selectTab(tab) {
    tabs.forEach((t) => {
      const active = t === tab;
      t.setAttribute("aria-selected", String(active));
      t.tabIndex = active ? 0 : -1;
      const panel = document.getElementById(t.getAttribute("aria-controls"));
      if (panel) panel.hidden = !active;
    });
  }
  App.selectTab = selectTab;

  tabs.forEach((tab, i) => {
    tab.addEventListener("click", () => selectTab(tab));
    tab.addEventListener("keydown", (e) => {
      if (e.key !== "ArrowRight" && e.key !== "ArrowLeft") return;
      const step = e.key === "ArrowRight" ? 1 : -1;
      const next = tabs[(i + step + tabs.length) % tabs.length];
      selectTab(next);
      next.focus();
    });
  });

  // Карточка «Практикам» в блоке «О нас» сразу открывает вкладку «Хочу обучать»
  $$("[data-tab='teach']").forEach((link) =>
    link.addEventListener("click", () => selectTab($("#tab-teach")))
  );
})();