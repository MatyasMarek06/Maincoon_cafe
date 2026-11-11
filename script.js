/* =========================================================
   Purrfect Maine – skripty
   - společné interaktivní prvky (menu, galerie, animace)
   - filtrování profilů koček
   - pomocník rezervačního formuláře (sloty, cena, volné dny koček)
   - drobnosti pro administraci (potvrzení, náhled fotky)
   Veškerá data ukládá a ověřuje server (PHP + MySQL).
   ========================================================= */

"use strict";

const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

const DAY_NAMES = ["neděle", "pondělí", "úterý", "středa", "čtvrtek", "pátek", "sobota"];

/* ---------- Pomocné funkce ---------- */

/** Převede "YYYY-MM-DD" na Date v místním čase (bez posunu časových pásem). */
function parseDate(value) {
  const [y, m, d] = value.split("-").map(Number);
  return new Date(y, m - 1, d);
}

function toDateValue(date) {
  const pad = (n) => String(n).padStart(2, "0");
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

function minutesToTime(min) {
  return `${String(Math.floor(min / 60)).padStart(2, "0")}:${String(min % 60).padStart(2, "0")}`;
}

function formatPrice(amount) {
  return `${amount.toLocaleString("cs-CZ")} Kč`;
}

/** Krátké oznámení ve spodní části obrazovky. */
function showToast(message) {
  let toast = $(".toast");
  if (!toast) {
    toast = document.createElement("div");
    toast.className = "toast";
    toast.setAttribute("role", "status");
    toast.setAttribute("aria-live", "polite");
    document.body.appendChild(toast);
  }
  toast.textContent = message;
  toast.classList.add("show");
  clearTimeout(showToast.timer);
  showToast.timer = setTimeout(() => toast.classList.remove("show"), 3500);
}

/* ---------- Společné prvky všech stránek ---------- */

/** Mobilní menu (hamburger). */
function initNavigation() {
  const toggle = $(".nav-toggle");
  const links = $(".nav-links");
  if (!toggle || !links) return;

  const close = () => {
    toggle.setAttribute("aria-expanded", "false");
    links.classList.remove("open");
  };

  toggle.addEventListener("click", () => {
    const open = toggle.getAttribute("aria-expanded") === "true";
    toggle.setAttribute("aria-expanded", String(!open));
    links.classList.toggle("open", !open);
  });

  links.addEventListener("click", (e) => {
    if (e.target.closest("a")) close();
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") close();
  });
}

/** Náhradní obrázek, pokud se fotka nenačte. */
function initImageFallback() {
  const fallback =
    "data:image/svg+xml;charset=utf-8," +
    encodeURIComponent(
      `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300">
        <rect width="400" height="300" fill="#e3d3bc"/>
        <text x="200" y="165" font-size="90" text-anchor="middle">🐈</text>
      </svg>`
    );

  const apply = (img) => {
    if (img.dataset.fallbackApplied) return;
    img.dataset.fallbackApplied = "1";
    img.src = fallback;
  };

  $$("img").forEach((img) => {
    if (img.complete && img.naturalWidth === 0 && img.getAttribute("src")) apply(img);
    img.addEventListener("error", () => apply(img));
  });
}

/** Postupné zobrazování sekcí při scrollování. */
function initReveal() {
  const items = $$(".reveal");
  if (!items.length) return;

  if (!("IntersectionObserver" in window)) {
    items.forEach((el) => el.classList.add("visible"));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("visible");
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.12 }
  );
  items.forEach((el) => observer.observe(el));
}

/** Potvrzovací dialog pro formuláře s atributem data-confirm (mazání, rušení). */
function initConfirmForms() {
  document.addEventListener("submit", (e) => {
    const form = e.target.closest("form[data-confirm]");
    if (form && !confirm(form.dataset.confirm)) e.preventDefault();
  });
}

/* ---------- Hlavní stránka ---------- */

/** Fotogalerie s lightboxem. */
function initGallery() {
  const items = $$(".gallery-item");
  const lightbox = $(".lightbox");
  if (!items.length || !lightbox) return;

  const lbImg = $("img", lightbox);
  const lbCaption = $("p", lightbox);
  const closeBtn = $(".lightbox-close", lightbox);
  let lastFocused = null;

  const open = (item) => {
    const img = $("img", item);
    lbImg.src = img.dataset.full || img.src;
    lbImg.alt = img.alt;
    lbCaption.textContent = $(".caption", item)?.textContent || "";
    lastFocused = item;
    lightbox.classList.add("open");
    document.body.style.overflow = "hidden";
    closeBtn.focus();
  };

  const close = () => {
    lightbox.classList.remove("open");
    document.body.style.overflow = "";
    if (lastFocused) lastFocused.focus();
  };

  items.forEach((item) => item.addEventListener("click", () => open(item)));
  closeBtn.addEventListener("click", close);
  lightbox.addEventListener("click", (e) => {
    if (e.target === lightbox) close();
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && lightbox.classList.contains("open")) close();
  });
}

/** Zvýraznění dnešního dne v tabulce otevírací doby. */
function initOpeningHours() {
  const today = new Date().getDay();
  $$(".hours tr[data-days]").forEach((row) => {
    if (row.dataset.days.split(",").map(Number).includes(today)) row.classList.add("today");
  });
}

/* ---------- Stránka koček ---------- */

function initCatFilters() {
  const buttons = $$(".filter-btn");
  const cards = $$(".cat-card");
  if (!buttons.length) return;

  buttons.forEach((btn) => {
    btn.addEventListener("click", () => {
      const filter = btn.dataset.filter;
      buttons.forEach((b) => {
        b.classList.toggle("active", b === btn);
        b.setAttribute("aria-pressed", String(b === btn));
      });
      cards.forEach((card) => {
        const tags = (card.dataset.tags || "").split(" ");
        card.classList.toggle("hidden", filter !== "all" && !tags.includes(filter));
      });
    });
  });
}

/** Animované měřáky povahových rysů. */
function initTraitBars() {
  const bars = $$(".trait-bar span[data-value]");
  if (!bars.length) return;

  const fill = (bar) => {
    bar.style.width = `${bar.dataset.value * 10}%`;
  };

  if (!("IntersectionObserver" in window)) {
    bars.forEach(fill);
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          fill(entry.target);
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.5 }
  );
  bars.forEach((bar) => observer.observe(bar));
}

/* =========================================================
   Rezervační formulář
   Nastavení (otevírací doba, ceny) posílá PHP v data-* atributech formuláře,
   takže pravidla jsou definovaná jen na jednom místě – v config.php.
   ========================================================= */
function initBooking() {
  const form = $("#booking-form");
  if (!form) return;

  const hours = JSON.parse(form.dataset.hours);
  const pricePerHour = Number(form.dataset.price);
  const catFee = Number(form.dataset.catFee);
  const maxPeople = Number(form.dataset.maxPeople);

  const els = {
    date: $("#date"),
    time: $("#time"),
    duration: $("#duration"),
    people: $("#people"),
    priceBase: $("#price-base"),
    priceCat: $("#price-cat"),
    priceCatRow: $("#price-cat-row"),
    priceTotal: $("#price-total"),
  };

  const catInputs = $$('input[name="cat_id"]', form);
  const selectedCat = () => catInputs.find((i) => i.checked);

  /** Překreslí nabídku časů podle dne a délky návštěvy. */
  function renderTimeSlots() {
    const previous = els.time.value;
    if (!els.date.value) {
      els.time.innerHTML = '<option value="">Nejprve vyberte datum</option>';
      return;
    }

    const date = parseDate(els.date.value);
    const [open, close] = hours[date.getDay()];
    const duration = Number(els.duration.value) * 60;
    const isToday = els.date.value === toDateValue(new Date());
    const now = new Date();
    const nowMinutes = now.getHours() * 60 + now.getMinutes();

    const slots = [];
    for (let m = open * 60; m + duration <= close * 60; m += 30) {
      if (isToday && m < nowMinutes + 30) continue;
      slots.push(m);
    }

    if (!slots.length) {
      els.time.innerHTML = '<option value="">Pro tento den už nejsou volné časy</option>';
      return;
    }

    els.time.innerHTML =
      '<option value="">Vyberte čas</option>' +
      slots
        .map((m) => `<option value="${minutesToTime(m)}">${minutesToTime(m)} – ${minutesToTime(m + duration)}</option>`)
        .join("");

    if ($(`option[value="${previous}"]`, els.time)) els.time.value = previous;
  }

  /** Zakáže kočky, které mají ve vybraný den volno. */
  function updateCatAvailability(announce) {
    const day = els.date.value ? parseDate(els.date.value).getDay() : null;
    catInputs.forEach((input) => {
      const restDays = input.dataset.restDays ? input.dataset.restDays.split(",").map(Number) : [];
      const resting = day !== null && restDays.includes(day);
      const label = input.nextElementSibling;
      input.disabled = resting;
      label.style.opacity = resting ? "0.45" : "";
      label.style.cursor = resting ? "not-allowed" : "";
      label.title = resting ? `${input.dataset.name} má v tento den volno` : "";
      if (resting && input.checked) {
        catInputs[0].checked = true;
        if (announce) showToast(`${input.dataset.name} má v ${DAY_NAMES[day]} volno – vybrali jsme „Kteroukoli“.`);
      }
    });
    updatePrice();
  }

  function setPeople(n) {
    els.people.value = Math.min(maxPeople, Math.max(1, Number(n) || 1));
    updatePrice();
  }

  function updatePrice() {
    const people = Number(els.people.value) || 1;
    const hrs = Number(els.duration.value);
    const withCat = Boolean(selectedCat() && Number(selectedCat().value));
    const base = people * hrs * pricePerHour;
    const fee = withCat ? catFee : 0;
    els.priceBase.textContent = formatPrice(base);
    els.priceCatRow.hidden = !fee;
    els.priceCat.textContent = formatPrice(fee);
    els.priceTotal.textContent = formatPrice(base + fee);
  }

  /* --- Události --- */
  els.date.addEventListener("change", () => {
    renderTimeSlots();
    updateCatAvailability(true);
  });
  els.duration.addEventListener("change", () => {
    renderTimeSlots();
    updatePrice();
  });
  els.people.addEventListener("change", () => setPeople(els.people.value));
  $$("[data-step]", form).forEach((btn) => {
    btn.addEventListener("click", () => setPeople(Number(els.people.value) + Number(btn.dataset.step)));
  });
  catInputs.forEach((input) => input.addEventListener("change", updatePrice));

  // Oprava pole odstraní jeho chybové zvýraznění
  form.addEventListener("input", (e) => {
    e.target.classList?.remove("invalid");
    const error = $(`#${e.target.id}-error`);
    if (error) error.textContent = "";
  });

  // Ochrana proti dvojímu odeslání
  form.addEventListener("submit", () => {
    const btn = $('button[type="submit"]', form);
    btn.disabled = true;
    btn.textContent = "Odesílám…";
  });

  /* --- Inicializace (časy vykreslil server, zde jen doplníme stav) --- */
  updateCatAvailability(false);
  updatePrice();
}

/* ---------- Administrace ---------- */

/** Živý náhled nahrávané fotky. */
function initPhotoPreview() {
  $$("input[type=file][data-preview]").forEach((input) => {
    const preview = $(input.dataset.preview);
    input.addEventListener("change", () => {
      const file = input.files[0];
      if (!file || !preview) return;
      const img = document.createElement("img");
      img.alt = "Náhled nové fotky";
      img.src = URL.createObjectURL(file);
      preview.replaceChildren(img);
    });
  });
}

/** Zobrazení hodnoty u posuvníků povahových rysů. */
function initRangeOutputs() {
  $$('input[type="range"]').forEach((range) => {
    const output = $(`output[for="${range.id}"]`);
    if (output) range.addEventListener("input", () => (output.textContent = range.value));
  });
}

/* ---------- Spuštění ---------- */
document.addEventListener("DOMContentLoaded", () => {
  initNavigation();
  initImageFallback();
  initReveal();
  initConfirmForms();
  initGallery();
  initOpeningHours();
  initCatFilters();
  initTraitBars();
  initBooking();
  initPhotoPreview();
  initRangeOutputs();
});
