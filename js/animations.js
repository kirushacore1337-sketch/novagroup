/* animations.js — одно аккуратное появление: шаги и колонки дорожной карты
   выезжают по очереди при первой прокрутке. Если пользователь отключил анимации
   в системе или IntersectionObserver недоступен, всё показывается сразу. */
(function () {
  "use strict";
  const { $$ } = App;
  if (App.reducedMotion || !("IntersectionObserver" in window)) return;

  const groups = [".steps", ".road"];
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add("is-visible");
        observer.unobserve(entry.target);
      });
    },
    { threshold: 0.15, rootMargin: "0px 0px -40px 0px" }
  );

  groups.forEach((selector) =>
    $$(selector).forEach((group) =>
      [...group.children].forEach((item, i) => {
        item.classList.add("reveal");
        item.style.setProperty("--d", i * 0.08 + "s");
        observer.observe(item);
      })
    )
  );
})();