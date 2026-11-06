/* hero.js — интерактив первого экрана: клик по иконке направления показывает карточку */
(function () {
  "use strict";
  const { $, $$ } = App;
  const panel = $("#orbit-panel");
  const buttons = $$(".orbit-btn");
  if (!panel || !buttons.length) return;

  const directions = {
    agency: { title: "Агентство", text: "Продвижение под ключ: SEO, таргет, контекст, SMM, сайты и брендинг.", href: "#agency", link: "Смотреть услуги" },
    school: { title: "Обучение", text: "Живые курсы от практиков, небольшие потоки и закрытая группа в Telegram.", href: "#education", link: "Смотреть курсы" },
    jobs: { title: "Вакансии", text: "Помогаем выпускникам выйти к работодателям, а компаниям найти специалистов.", href: "#roadmap", link: "Что будет дальше" },
  };

  buttons.forEach((btn) =>
    btn.addEventListener("click", () => {
      const d = directions[btn.dataset.dir];
      if (!d) return;
      buttons.forEach((b) => b.setAttribute("aria-pressed", String(b === btn)));

      panel.replaceChildren();
      const title = document.createElement("h3");
      const text = document.createElement("p");
      const link = document.createElement("a");
      text.className = "lead";
      text.style.fontSize = "1rem";
      title.textContent = d.title;
      text.textContent = d.text;
      link.href = d.href;
      link.textContent = d.link;
      panel.append(title, text, link);
    })
  );
})();