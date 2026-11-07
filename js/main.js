/* main.js — точка входа. Подключается первым: создаёт общий объект App с утилитами,
   на который опираются остальные модули (menu, hero, tabs, modal, form, animations). */
(function () {
  "use strict";

  const App = (window.App = {
    $: (selector, ctx = document) => ctx.querySelector(selector),
    $$: (selector, ctx = document) => [...ctx.querySelectorAll(selector)],
    reducedMotion: matchMedia("(prefers-reduced-motion: reduce)").matches,
  });

  // Если локальная картинка не найдена, скрываем её, чтобы не было «битого» значка
  App.$$("img").forEach((img) => {
    const hide = () => (img.style.visibility = "hidden");
    if (img.complete && img.naturalWidth === 0) hide();
    img.addEventListener("error", hide);
  });

  // Год в футере
  const year = App.$("#year");
  if (year) year.textContent = new Date().getFullYear();
})();