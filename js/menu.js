/* menu.js — бургер-меню, закрытие по клику и Esc, тень шапки при прокрутке */
(function () {
  "use strict";
  const { $, $$ } = App;
  const burger = $("#burger");
  const nav = $("#nav");
  const header = $("#header");
  if (!burger || !nav) return;

  function setMenu(open) {
    nav.classList.toggle("open", open);
    burger.setAttribute("aria-expanded", String(open));
    burger.setAttribute("aria-label", open ? "Закрыть меню" : "Открыть меню");
  }
  App.closeMenu = () => setMenu(false);

  burger.addEventListener("click", () => setMenu(!nav.classList.contains("open")));
  $$("a", nav).forEach((a) => a.addEventListener("click", () => setMenu(false)));
  addEventListener("keydown", (e) => {
    if (e.key === "Escape") setMenu(false);
  });
  // Если окно расширили до десктопа, меню сбрасываем
  matchMedia("(min-width: 861px)").addEventListener("change", (e) => {
    if (e.matches) setMenu(false);
  });

  // Тень шапки после прокрутки
  if (header) {
    const onScroll = () => header.classList.toggle("is-scrolled", scrollY > 8);
    addEventListener("scroll", onScroll, { passive: true });
    onScroll();
  }
})();