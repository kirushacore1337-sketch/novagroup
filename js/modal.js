/* modal.js — открытие и закрытие модальных окон (<dialog>) */
(function () {
  "use strict";
  const { $, $$ } = App;

  $$("[data-open]").forEach((trigger) =>
    trigger.addEventListener("click", (e) => {
      e.preventDefault();
      if (App.closeMenu) App.closeMenu();
      const modal = document.getElementById(trigger.dataset.open);
      if (modal && typeof modal.showModal === "function" && !modal.open) modal.showModal();
    })
  );

  $$("dialog").forEach((modal) => {
    // Клик по затемнению закрывает окно
    modal.addEventListener("click", (e) => {
      if (e.target === modal) modal.close();
    });
    const close = $("[data-close]", modal);
    if (close) close.addEventListener("click", () => modal.close());
  });
})();