/* می‌جا — افکت‌ها و انیمیشن‌ها (GSAP / Swiper / Chart.js / Leaflet / Confetti) */
(function () {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s), $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const reduce = matchMedia('(prefers-reduced-motion:reduce)').matches;
  const g = window.gsap;
  if (reduce) document.documentElement.classList.add('reduced');
  const fa = n => String(n).replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
  const sep = n => fa(Math.round(n).toLocaleString('en-US')).replace(/,/g, '٬');

  /* ---------- نوار پیشرفت و محو شدن صفحه هنگام ناوبری ---------- */
  const bar = document.createElement('div'); bar.className = 'navbar-progress'; document.body.appendChild(bar);
  document.addEventListener('click', e => {
    const a = e.target.closest('a[href]');
    if (!a || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || a.target === '_blank' || a.hasAttribute('download') || a.hasAttribute('data-lightbox')) return;
    const u = new URL(a.href, location.href);
    if (u.origin !== location.origin || (u.pathname === location.pathname && u.search === location.search) || u.hash) return;
    if (g && !reduce) g.to(bar, { scaleX: .85, duration: 1.4, ease: 'power2.out' });
    document.body.classList.add('leaving');
  });
  window.addEventListener('pageshow', () => { document.body.classList.remove('leaving'); if (g) g.set(bar, { scaleX: 0 }); });

  /* ---------- هدر ---------- */
  const top = $('.topbar'); let lastY = scrollY;
  if (top) addEventListener('scroll', () => {
    const y = scrollY; top.classList.toggle('scrolled', y > 6);
    top.classList.toggle('hide', y > lastY && y > 140); lastY = y;
  }, { passive: true });

  /* ---------- نشانگر لغزان منوی پایین ---------- */
  const nav = $('.bottomnav ul');
  if (nav) {
    const ind = document.createElement('span'); ind.className = 'indicator'; nav.prepend(ind);
    // موقعیت نشانگر با offsetLeft نسبت به ul محاسبه می‌شود
    const placeAbs = () => { const on = $('a.on', nav); if (!on) { ind.style.opacity = 0; return; } const li = on.parentElement; ind.style.opacity = 1; ind.style.left = li.offsetLeft + 'px'; ind.style.width = li.offsetWidth + 'px'; ind.style.transform = 'none'; ind.style.insetInlineStart = 'auto'; };
    placeAbs(); addEventListener('resize', placeAbs); setTimeout(placeAbs, 400);
  }

  /* ---------- ورود تدریجی عناصر هنگام اسکرول ---------- */
  if (g && window.ScrollTrigger && !reduce) {
    g.registerPlugin(ScrollTrigger);
    const sel = '.pcard,.cat,.tile,.feat .card,.dest,.item-row,.step,.testi,.faq details,.card.rv,[data-reveal]';
    $$(sel).forEach(el => { if (!el.closest('.swiper')) el.style.opacity = 0; });
    ScrollTrigger.batch($$(sel).filter(el => !el.closest('.swiper')), {
      start: 'top 92%', once: true,
      onEnter: els => g.fromTo(els, { y: 34, opacity: 0, scale: .97 }, { y: 0, opacity: 1, scale: 1, duration: .7, ease: 'power3.out', stagger: .08, clearProps: 'transform' }),
    });
    // عنوان بخش‌ها
    $$('.sec-h,.page-h').forEach(el => g.from(el, { y: 20, opacity: 0, duration: .6, ease: 'power3.out', scrollTrigger: { trigger: el, start: 'top 95%', once: true } }));
  } else { $$('[data-reveal]').forEach(e => e.style.opacity = 1); }

  /* ---------- شمارنده‌ی عددی ---------- */
  $$('[data-count]').forEach(el => {
    const to = +el.dataset.count, dec = +el.dataset.dec || 0, suffix = el.dataset.suffix || '';
    const fmt = v => (dec ? fa(v.toFixed(dec)).replace('.', '٫') : sep(v)) + suffix;
    if (!g || reduce) { el.textContent = fmt(to); return; }
    const o = { v: 0 };
    ScrollTrigger.create({ trigger: el, start: 'top 95%', once: true, onEnter: () => g.to(o, { v: to, duration: 1.8, ease: 'power2.out', onUpdate: () => el.textContent = fmt(o.v) }) });
    el.textContent = fmt(0);
  });

  /* ---------- تصاویر: محو شدن پس از بارگذاری ---------- */
  $$('.pcard .ph img').forEach(i => { const ok = () => i.classList.add('loaded'); i.complete ? ok() : i.addEventListener('load', ok); i.addEventListener('error', ok); });

  /* ---------- کارت سه‌بعدی روی دسکتاپ ---------- */
  if (g && !reduce && matchMedia('(hover:hover) and (pointer:fine)').matches) {
    $$('.pcard').forEach(c => {
      c.addEventListener('mousemove', e => { const r = c.getBoundingClientRect(), x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5; g.to(c, { rotateY: x * 7, rotateX: -y * 7, y: -6, duration: .4, transformPerspective: 900, ease: 'power2.out' }); });
      c.addEventListener('mouseleave', () => g.to(c, { rotateY: 0, rotateX: 0, y: 0, duration: .6, ease: 'power3.out' }));
    });
  }

  /* ---------- پارالاکس صحنه‌ها ---------- */
  $$('[data-parallax]').forEach(box => {
    if (reduce) return;
    const layers = $$('.layer', box).map(l => ({ el: l, d: +l.dataset.depth || 10 }));
    const move = (x, y) => layers.forEach(({ el, d }) => el.style.translate = `${x * d}px ${y * d * .5}px`);
    box.addEventListener('mousemove', e => { const r = box.getBoundingClientRect(); move(-((e.clientX - r.left) / r.width - .5) * .8, -((e.clientY - r.top) / r.height - .5) * .8); });
    box.addEventListener('mouseleave', () => move(0, 0));
    if (g && window.ScrollTrigger) layers.forEach(({ el, d }) => g.to(el, { y: -d * .9, ease: 'none', scrollTrigger: { trigger: box, start: 'top top', end: 'bottom top', scrub: true } }));
  });

  /* ---------- چرخش کلمات عنوان ---------- */
  $$('.rotor').forEach(r => {
    const w = $$('span', r); if (w.length < 2) return; let i = 0; w[0].classList.add('on');
    r.style.minWidth = Math.max(...w.map(x => x.offsetWidth)) + 'px';
    setInterval(() => { const p = w[i]; p.classList.remove('on'); p.classList.add('out'); i = (i + 1) % w.length; const n = w[i]; n.classList.remove('out'); void n.offsetWidth; n.classList.add('on'); setTimeout(() => p.classList.remove('out'), 700); }, 2600);
  });

  /* ---------- هیرو: ورود سینمایی ---------- */
  if (g && !reduce && $('.stage')) {
    g.timeline({ defaults: { ease: 'power3.out' } })
      .from('.stage .scene .layer', { y: 90, opacity: 0, duration: 1.1, stagger: .09 })
      .from('.stage-in .badge', { y: 20, opacity: 0, duration: .6 }, .25)
      .from('.stage-in h1', { y: 36, opacity: 0, duration: .8 }, .35)
      .from('.stage-in .sub', { y: 24, opacity: 0, duration: .7 }, .5)
      .from('.trust > div', { y: 20, opacity: 0, stagger: .1, duration: .6 }, .65)
      .from('.stage .searchbar', { y: 70, opacity: 0, duration: .9 }, .55)
      .from('.stage .chip', { scale: .4, opacity: 0, stagger: .15, duration: .8, ease: 'back.out(1.8)' }, .9);
  }

  /* ---------- اسلایدرها ---------- */
  if (window.Swiper) {
    const cfg = {
      cards: { slidesPerView: 1.15, spaceBetween: 14, freeMode: false, breakpoints: { 560: { slidesPerView: 2.1 }, 900: { slidesPerView: 3.1, spaceBetween: 18 }, 1180: { slidesPerView: 4, spaceBetween: 20 } } },
      cats: { slidesPerView: 'auto', spaceBetween: 10, freeMode: true },
      testi: { slidesPerView: 1.1, spaceBetween: 14, loop: false, autoplay: { delay: 4500, disableOnInteraction: false }, breakpoints: { 700: { slidesPerView: 2.1 }, 1000: { slidesPerView: 3 } } },
      gallery: { slidesPerView: 1, spaceBetween: 0 },
    };
    $$('[data-swiper]').forEach(el => {
      const sw = el.querySelector('.swiper') || el, name = el.dataset.swiper, host = el;
      const opts = Object.assign({ rtl: true, grabCursor: true, watchOverflow: true }, cfg[name] || {});
      if ($('.sw-next', host) && $('.sw-prev', host)) opts.navigation = { nextEl: $('.sw-next', host), prevEl: $('.sw-prev', host) };
      if ($('.swiper-pagination', host)) opts.pagination = { el: $('.swiper-pagination', host), clickable: true };
      const inst = new Swiper(sw, opts);
      const cnt = $('.count', host); if (cnt) { const upd = () => cnt.textContent = fa(inst.realIndex + 1) + ' از ' + fa(inst.slides.length); inst.on('slideChange', upd); upd(); }
    });
  }

  /* ---------- نمودارها ---------- */
  if (window.Chart) {
    Chart.defaults.font.family = 'Vazir, sans-serif'; Chart.defaults.color = '#6b7080';
    $$('canvas[data-chart]').forEach(cv => {
      const d = JSON.parse(cv.dataset.chart), ctx = cv.getContext('2d'), h = cv.parentElement.clientHeight || 230;
      const gr = ctx.createLinearGradient(0, 0, 0, h); gr.addColorStop(0, d.color || 'rgba(255,98,30,.55)'); gr.addColorStop(1, 'rgba(255,178,26,0)');
      const type = d.type || 'line';
      new Chart(cv, {
        type, data: { labels: d.labels, datasets: [{ data: d.data, borderColor: d.border || '#FF5A1F', backgroundColor: type === 'bar' ? (d.bar || '#FF7A00') : gr, fill: true, tension: .28, borderWidth: 3, pointRadius: 0, pointHoverRadius: 6, pointBackgroundColor: '#fff', pointBorderColor: '#FF5A1F', pointBorderWidth: 3, borderRadius: 10, maxBarThickness: 34 }] },
        options: { responsive: true, maintainAspectRatio: false, animation: { duration: reduce ? 0 : 1400, easing: 'easeOutQuart' }, interaction: { intersect: false, mode: 'index' },
          plugins: { legend: { display: false }, tooltip: { rtl: true, backgroundColor: 'rgba(17,18,22,.92)', padding: 12, cornerRadius: 12, displayColors: false, callbacks: { label: c => (d.unit === 'toman' ? sep(c.parsed.y) + ' تومان' : fa(c.parsed.y) + (d.unit || '')) } } },
          scales: { x: { grid: { display: false }, border: { display: false }, reverse: true }, y: { position: 'right', beginAtZero: true, grid: { color: 'rgba(17,18,22,.06)' }, border: { display: false }, ticks: { maxTicksLimit: 5, callback: v => d.unit === 'toman' ? fa((v / 1e6).toFixed(v % 1e6 ? 1 : 0)) + 'م' : fa(v) } } } },
      });
    });
    $$('canvas[data-donut]').forEach(cv => {
      const d = JSON.parse(cv.dataset.donut);
      new Chart(cv, { type: 'doughnut', data: { labels: d.labels, datasets: [{ data: d.data, backgroundColor: ['#F26B0F', '#FFB21A', '#19B5AC', '#2A2C33', '#7CC8F0', '#2FAE6B'], borderWidth: 4, borderColor: '#fff', hoverOffset: 10 }] }, options: { responsive: true, maintainAspectRatio: false, cutout: '68%', animation: { duration: reduce ? 0 : 1400 }, plugins: { legend: { position: 'bottom', rtl: true, labels: { usePointStyle: true, padding: 14 } }, tooltip: { rtl: true, callbacks: { label: c => ' ' + c.label + ': ' + fa(c.parsed) } } } } });
    });
  }

  /* ---------- نقشه (Leaflet + OSM) ---------- */
  $$('[data-map]').forEach(el => {
    if (!window.L) return; const lat = +el.dataset.lat, lng = +el.dataset.lng;
    const m = L.map(el, { scrollWheelZoom: false, zoomControl: true }).setView([lat, lng], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, attribution: '© OpenStreetMap' }).addTo(m);
    L.circle([lat, lng], { radius: 700, color: '#F26B0F', fillColor: '#FF7A00', fillOpacity: .25, weight: 2 }).addTo(m);
    L.marker([lat, lng], { icon: L.divIcon({ className: '', html: '<img src="/img/mija-mark.svg" width="40" height="40" style="filter:drop-shadow(0 6px 8px rgba(0,0,0,.35))">', iconSize: [40, 40], iconAnchor: [20, 36] }) }).addTo(m);
  });

  /* ---------- شیت رزرو در موبایل ---------- */
  const bb = $('.booking-box');
  if (bb) {
    const back = $('#sheet-back'); const set = o => { bb.classList.toggle('open', o); back && back.classList.toggle('open', o); document.body.style.overflow = o ? 'hidden' : ''; };
    $$('[data-open-book]').forEach(b => b.addEventListener('click', () => set(true)));
    $$('[data-close-book]').forEach(b => b.addEventListener('click', () => set(false)));
    back && back.addEventListener('click', () => set(false));
  }

  /* ---------- مراحل فرم اقامتگاه در موبایل ---------- */
  const pf = $('#pform');
  if (pf) {
    const steps = $$('.pf-step', pf), dots = $$('.pf-steps i'), mq = matchMedia('(max-width:860px)'); let i = 0;
    const show = n => { i = Math.max(0, Math.min(steps.length - 1, n)); steps.forEach((s, k) => s.classList.toggle('on', k === i)); dots.forEach((d, k) => d.classList.toggle('on', k <= i)); $('#pf-prev').style.visibility = i ? 'visible' : 'hidden'; const last = i === steps.length - 1; $('#pf-next').hidden = last; $('#pf-save').hidden = !last; if (mq.matches) scrollTo({ top: 0, behavior: 'smooth' }); };
    $('#pf-next').addEventListener('click', () => { const cur = steps[i]; const bad = $$('[required]', cur).find(x => !x.value); if (bad && mq.matches) { bad.focus(); bad.reportValidity && bad.reportValidity(); return; } show(i + 1); });
    $('#pf-prev').addEventListener('click', () => show(i - 1));
    const apply = () => { if (mq.matches) show(i); else { steps.forEach(s => s.classList.add('on')); $('#pf-save').hidden = false; $('#pf-next').hidden = true; } };
    mq.addEventListener('change', apply); apply();
  }

  /* ---------- منوی اشتراک ---------- */
  const sh = $('[data-share-toggle]');
  if (sh) { const m = sh.parentElement.querySelector('.share-menu'); sh.addEventListener('click', e => { e.stopPropagation(); if (navigator.share && matchMedia('(max-width:860px)').matches) { navigator.share({ title: document.title, url: location.href }).catch(() => {}); return; } m.classList.toggle('open'); }); document.addEventListener('click', e => { if (!m.contains(e.target)) m.classList.remove('open'); }); }

  /* ---------- شمارش معکوس سفر ---------- */
  $$('[data-countdown]').forEach(el => {
    const t = new Date(el.dataset.countdown).getTime(); const tick = () => { let d = t - Date.now(); if (d < 0) { el.textContent = 'در حال اقامت'; return; } const day = Math.floor(d / 864e5), h = Math.floor(d % 864e5 / 36e5), m = Math.floor(d % 36e5 / 6e4); el.innerHTML = `<b>${fa(day)}</b> روز <b>${fa(h)}</b> ساعت <b>${fa(m)}</b> دقیقه`; };
    tick(); setInterval(tick, 30000);
  });

  /* ---------- قلب: انیمیشن ---------- */
  document.addEventListener('click', e => { const f = e.target.closest('[data-fav]'); if (f) { f.classList.add('pop'); setTimeout(() => f.classList.remove('pop'), 600); } }, true);

  /* ---------- بازدیدهای اخیر ---------- */
  const rp = $('#recent-data');
  if (rp) { try { const it = { slug: rp.dataset.slug, title: rp.dataset.title, img: rp.dataset.img, url: rp.dataset.url, price: rp.dataset.price }; let l = JSON.parse(localStorage.getItem('mija-recent') || '[]').filter(x => x.slug !== it.slug); l.unshift(it); localStorage.setItem('mija-recent', JSON.stringify(l.slice(0, 8))); } catch (_) {} }
  const rbox = $('#recent-box');
  if (rbox) { try { const l = JSON.parse(localStorage.getItem('mija-recent') || '[]'); if (l.length) { rbox.classList.add('show'); $('.recent-list', rbox).innerHTML = l.map(x => `<a class="pcard swiper-slide" href="${x.url}"><div class="ph"><img class="loaded" src="${x.img}" alt=""></div><div class="pb"><h3>${x.title.replace(/</g, '&lt;')}</h3><div class="price"><small>شروع از</small><b>${x.price}</b></div></div></a>`).join(''); } } catch (_) {} }

  /* ---------- جشن رزرو موفق ---------- */
  if ($('[data-confetti]') && window.confetti && !reduce) {
    const end = Date.now() + 1600, colors = ['#F26B0F', '#FF9A1F', '#FFD76A', '#7CC8F0', '#2FAE6B'];
    (function f() { confetti({ particleCount: 5, angle: 60, spread: 70, origin: { x: 0, y: .7 }, colors }); confetti({ particleCount: 5, angle: 120, spread: 70, origin: { x: 1, y: .7 }, colors }); if (Date.now() < end) requestAnimationFrame(f); })();
  }
})();
