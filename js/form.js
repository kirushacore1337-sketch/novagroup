/* form.js — проверка и отправка заявок на server/send.php */
(function () {
  "use strict";
  const { $, $$ } = App;

  // Адрес Cloudflare Worker (для GitHub Pages). Пусто = использовать PHP: server/send.php
  const WORKER_URL = "";
  const ENDPOINT = WORKER_URL || "server/send.php";
  const TG_RE = /^@?[A-Za-z0-9_]{4,32}$/;

  $$("[data-form]").forEach((form) => {
    const status = $(".status", form);
    const submit = $("button[type=submit]", form);

    function show(message, kind) {
      status.textContent = message;
      status.className = "status" + (kind ? " " + kind : "");
    }

    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const name = form.elements.name.value.trim();
      const tg = form.elements.tg.value.trim();
      const agree = form.elements.agree.checked;

      const nameOk = name.length > 0 && name.length <= 100;
      const tgOk = TG_RE.test(tg);
      form.elements.name.setAttribute("aria-invalid", String(!nameOk));
      form.elements.tg.setAttribute("aria-invalid", String(!tgOk));

      if (!nameOk || !tgOk) return show("Укажите имя и Telegram в формате @username.", "err");
      if (!agree) return show("Нужно согласие на обработку персональных данных.", "err");

      submit.disabled = true;
      show("Отправляем…");
      try {
        const res = await fetch(ENDPOINT, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ type: form.dataset.type, name, tg }),
        });
        if (!res.ok) {
          console.error("send.php:", res.status, await res.text());
          throw new Error("HTTP " + res.status);
        }
        show("Заявка отправлена. Мы свяжемся с вами в Telegram.", "ok");
        form.reset();
      } catch (err) {
        show("Не удалось отправить. Напишите нам в Telegram напрямую.", "err");
      } finally {
        submit.disabled = false;
        const WORKER_URL = "https://novagroup-form.kirushacore-1337.workers.dev";
      }
    });
  });
})();
