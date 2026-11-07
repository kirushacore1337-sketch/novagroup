/* hero.js — интерактив первого экрана: клик по иконке направления меняет карточку и картинку */
(function () {
  "use strict";
  const { $, $$ } = App;
  const panel = $("#orbit-panel");
  const buttons = $$(".orbit-btn");
  if (!panel || !buttons.length) return;

  const title = $("h3", panel);
  const text = $("p", panel);
  const link = $("a", panel);
  const img = $("#orbit-img");

  const base = {
    title: "Единая экосистема",
    text: "Агентство, обучение и команда: три направления одного роста.",
    img: "img/hero/panel-default.svg",
  };
  const directions = {
    agency: { title: "Агентство", text: "Продвижение под ключ: SEO, таргет, контекст, SMM, сайты и брендинг.", href: "#agency", link: "Смотреть услуги", img: "img/hero/panel-agency.svg" },
    school: { title: "Обучение", text: "Живые курсы от практиков, небольшие потоки и закрытая группа в Telegram.", href: "#education", link: "Смотреть курсы", img: "img/hero/panel-school.svg" },
    jobs: { title: "Вакансии", text: "Помогаем выпускникам выйти к работодателям, а компаниям найти специалистов.", href: "#roadmap", link: "Что будет дальше", img: "img/hero/panel-jobs.svg" },
  };

  function render(d, active) {
    buttons.forEach((b) => b.setAttribute("aria-pressed", String(b === active)));
    title.textContent = d.title;
    text.textContent = d.text;
    img.src = d.img;
    link.hidden = !d.href;
    if (d.href) {
      link.href = d.href;
      link.textContent = d.link;
    }
  }

  buttons.forEach((btn) =>
    btn.addEventListener("click", () => {
      const d = directions[btn.dataset.dir];
      if (!d) return;
      // Повторный клик по активной иконке возвращает исходную карточку
      if (btn.getAttribute("aria-pressed") === "true") render(base, null);
      else render(d, btn);
    })
  );
})();
