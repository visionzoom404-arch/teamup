(function () {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const csrf = () => ($('meta[name=csrf-token]') || {}).content;

  /* ---------- PWA ---------- */
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
  }
  let deferred;
  const bar = $('#install');
  const inApp = /MijaApp\//.test(navigator.userAgent);
  const appUrl = bar && bar.dataset.app;
  const isAndroid = /Android/i.test(navigator.userAgent);
  if (inApp && bar) bar.remove();
  else if (appUrl && isAndroid && bar) {
    // اپلیکیشن بومی اندروید در دسترس است: به‌جای نصب PWA، به صفحه‌ی دانلود می‌بریم
    $('#install-t').textContent = 'اپلیکیشن اندروید می‌جا';
    $('#install-d').textContent = 'سریع‌تر و راحت‌تر؛ دانلود مستقیم یا از بازار و مایکت';
    $('#install-btn').textContent = 'دریافت';
    let hide = false; try { hide = Date.now() - (+localStorage.getItem('mija-app-x') || 0) < 7 * 864e5; } catch (_) {}
    if (!hide && !/^\/app/.test(location.pathname)) setTimeout(() => bar.classList.add('show'), 3500);
  }
  window.addEventListener('beforeinstallprompt', e => {
    e.preventDefault(); deferred = e;
    if (appUrl || inApp) return;
    try { if (localStorage.getItem('mija-install-x')) return; } catch (_) {}
    bar && bar.classList.add('show');
  });
  $('#install-btn') && $('#install-btn').addEventListener('click', async () => {
    if (appUrl && isAndroid) { location.href = appUrl; return; }
    if (!deferred) return; deferred.prompt(); await deferred.userChoice; deferred = null; bar.classList.remove('show');
  });
  $$('[data-install]').forEach(b => b.addEventListener('click', async () => {
    if (deferred) { deferred.prompt(); await deferred.userChoice; deferred = null; return; }
    alert('برای نصب: در مرورگر موبایل از منوی ⋮ (اندروید) یا دکمه‌ی اشتراک‌گذاری (آیفون) گزینه‌ی «افزودن به صفحه اصلی» را بزنید.');
  }));
  $('#install-x') && $('#install-x').addEventListener('click', () => {
    bar.classList.remove('show'); try { localStorage.setItem(appUrl ? 'mija-app-x' : 'mija-install-x', appUrl ? String(Date.now()) : '1'); } catch (_) {}
  });

  /* ---------- منوی بیشتر ---------- */
  const sheet = $('#sheet'), back = $('#sheet-back');
  const toggleSheet = o => { sheet && sheet.classList.toggle('open', o); back && back.classList.toggle('open', o); };
  $('#more-btn') && $('#more-btn').addEventListener('click', () => toggleSheet(true));
  back && back.addEventListener('click', () => toggleSheet(false));

  /* ---------- علاقه‌مندی ---------- */
  document.addEventListener('click', async e => {
    const f = e.target.closest('[data-fav]'); if (!f) return;
    e.preventDefault(); e.stopPropagation();
    try {
      const r = await fetch(f.dataset.fav, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' } });
      const j = await r.json(); f.classList.toggle('on', j.fav);
    } catch (_) {}
  });

  /* ---------- پیام‌های موقت ---------- */
  $$('.alert.ok').forEach(a => setTimeout(() => { a.style.transition = '.5s'; a.style.opacity = 0; setTimeout(() => a.remove(), 500); }, 6000));

  /* ---------- فیلتر موبایل ---------- */
  $$('[data-toggle-filters]').forEach(b => b.addEventListener('click', () => $('#filters').classList.toggle('open')));

  /* ---------- نمایش مبلغ به تومان ---------- */
  $$('input[data-money]').forEach(i => {
    const h = document.createElement('div'); h.className = 'help'; i.after(h);
    const u = () => { const v = parseInt(i.value || 0, 10); h.textContent = v ? Jalali.fa(v.toLocaleString('en-US')).replace(/,/g, '٬') + ' تومان' : ''; };
    i.addEventListener('input', u); u();
  });

  /* ---------- شمارنده ---------- */
  $$('.counter').forEach(c => {
    const inp = $('input', c), out = $('b', c);
    const min = +c.dataset.min || 0, max = +c.dataset.max || 99;
    const set = v => { v = Math.max(min, Math.min(max, v)); inp.value = v; out.textContent = Jalali.fa(v); inp.dispatchEvent(new Event('change', { bubbles: true })); };
    $('[data-dec]', c).addEventListener('click', () => set(+inp.value - 1));
    $('[data-inc]', c).addEventListener('click', () => set(+inp.value + 1));
  });

  /* ---------- گالری و نمایش تمام‌صفحه (با ورق زدن) ---------- */
  const lb = $('#lightbox');
  let lbList = [], lbIdx = 0;
  const lbShow = i => { if (!lbList.length) return; lbIdx = (i + lbList.length) % lbList.length; $('img', lb).src = lbList[lbIdx].getAttribute('href'); const c = $('.lb-count', lb); if (c) c.textContent = lbList.length > 1 ? (lbIdx + 1) + ' / ' + lbList.length : ''; lb.classList.toggle('single', lbList.length < 2); };
  $$('[data-lightbox]').forEach(a => a.addEventListener('click', e => {
    e.preventDefault(); if (!lb) return;
    const g = a.dataset.g; lbList = g ? $$('[data-lightbox][data-g="' + g + '"]') : [a]; lb.classList.add('open'); lbShow(lbList.indexOf(a));
  }));
  if (lb) {
    lb.addEventListener('click', e => { if (e.target === lb || e.target.closest('.lb-x')) lb.classList.remove('open'); });
    const pv = $('.lb-prev', lb), nx = $('.lb-next', lb);
    pv && pv.addEventListener('click', e => { e.stopPropagation(); lbShow(lbIdx - 1); }); nx && nx.addEventListener('click', e => { e.stopPropagation(); lbShow(lbIdx + 1); });
    document.addEventListener('keydown', e => { if (!lb.classList.contains('open')) return; if (e.key === 'Escape') lb.classList.remove('open'); if (e.key === 'ArrowLeft') lbShow(lbIdx + 1); if (e.key === 'ArrowRight') lbShow(lbIdx - 1); });
    let tx = null; lb.addEventListener('touchstart', e => { tx = e.touches[0].clientX; }, { passive: true });
    lb.addEventListener('touchend', e => { if (tx === null) return; const d = e.changedTouches[0].clientX - tx; if (Math.abs(d) > 50) lbShow(lbIdx + (d > 0 ? -1 : 1)); tx = null; });
  }

  /* ---------- پیش‌نمایش آپلود ---------- */
  $$('input[type=file][data-preview]').forEach(i => i.addEventListener('change', () => {
    const t = $(i.dataset.preview); if (!t) return; t.innerHTML = '';
    Array.from(i.files).forEach(f => { const im = document.createElement('img'); im.src = URL.createObjectURL(f); im.style.cssText = 'width:84px;height:84px;object-fit:cover;border-radius:12px'; t.appendChild(im); });
  }));

  /* ---------- انتخابگر بازه‌ی شمسی ---------- */
  function RangePicker(root) {
    const inIn = $('[data-in]', root), inOut = $('[data-out]', root), pop = $('.rp-pop', root);
    const labelIn = $('[data-label-in]', root), labelOut = $('[data-label-out]', root);
    const unavail = new Set(JSON.parse(root.dataset.unavailable || '[]'));
    const prices = JSON.parse(root.dataset.prices || '{}'), base = +root.dataset.base || 0;
    const short = p => p >= 1e6 ? Jalali.fa((p / 1e6).toFixed(1)).replace('.', '٫') + 'م' : Jalali.fa(Math.round(p / 1000)) + 'ه';
    const today = Jalali.todayISO();
    let [vy, vm] = Jalali.todayJ();
    let start = null, end = null;
    const narrow = () => window.matchMedia('(max-width:860px)').matches;

    const parseVal = v => { const m = /^(\d{4})\/(\d{2})\/(\d{2})$/.exec(v || ''); return m ? Jalali.iso(+m[1], +m[2], +m[3]) : null; };
    start = parseVal(inIn.value); end = parseVal(inOut.value);
    if (start) [vy, vm] = Jalali.parseISO(start).slice(0, 2);

    const firstBlockedAfter = s => { let d = new Date(s + 'T00:00:00'); for (let i = 0; i < 400; i++) { const k = d.getFullYear() + '-' + Jalali.pad(d.getMonth() + 1) + '-' + Jalali.pad(d.getDate()); if (unavail.has(k)) return k; d.setDate(d.getDate() + 1); } return '9999-12-31'; };
    const label = iso => { if (!iso) return null; const [y, m, d] = Jalali.parseISO(iso); return Jalali.fa(d) + ' ' + Jalali.months[m - 1]; };
    const sync = () => {
      const [a, b] = [start, end].map(x => x ? Jalali.fmt(...Jalali.parseISO(x)) : '');
      inIn.value = a; inOut.value = b;
      labelIn.textContent = label(start) || 'انتخاب تاریخ'; labelOut.textContent = label(end) || 'انتخاب تاریخ';
      root.dispatchEvent(new CustomEvent('rp:change', { bubbles: true, detail: { start, end } }));
    };

    function month(jy, jm) {
      const len = Jalali.len(jy, jm), off = Jalali.dowSat0(jy, jm, 1);
      let h = '<div class="rp-grid">' + ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'].map(d => '<div class="rp-dow">' + d + '</div>').join('');
      h += '<span></span>'.repeat(off);
      const limit = start && !end ? firstBlockedAfter(start) : null;
      for (let d = 1; d <= len; d++) {
        const iso = Jalali.iso(jy, jm, d), col = (off + d - 1) % 7;
        let dis = iso < today;
        if (!dis) { if (start && !end) dis = iso > limit || (iso <= start && unavail.has(iso)); else dis = unavail.has(iso); }
        const cls = ['rp-day']; if (col === 6) cls.push('fri'); if (iso === today) cls.push('today');
        if (iso === start) cls.push('start'); if (iso === end) cls.push('end');
        if (start && end && iso > start && iso < end) cls.push('range');
        const pr = prices[iso]; if (pr && base && pr > base * 1.05) cls.push('hot');
        const booked = unavail.has(iso) && iso >= today; if (booked) cls.push('booked'); else if (iso < today) cls.push('past');
        h += '<button type="button" class="' + cls.join(' ') + '" data-iso="' + iso + '"' + (dis ? ' disabled' : '') + '>' + Jalali.fa(d) + (booked ? '<small>پر</small>' : (pr && iso >= today ? '<small>' + short(pr) + '</small>' : '')) + '</button>';
      }
      return '<div><div class="rp-head"><span>' + Jalali.months[jm - 1] + ' ' + Jalali.fa(jy) + '</span></div>' + h + '</div></div>';
    }
    function render() {
      const n = narrow() ? 1 : 2;
      let html = '<div class="rp-head" style="margin-bottom:6px"><button type="button" class="rp-nav" data-nav="1" aria-label="ماه بعد">›</button><span class="muted small">' + (start && !end ? 'تاریخ خروج را انتخاب کنید' : 'تاریخ ورود را انتخاب کنید') + '</span><button type="button" class="rp-nav" data-nav="-1" aria-label="ماه قبل">‹</button></div><div class="rp-months">';
      let y = vy, m = vm;
      for (let i = 0; i < n; i++) { html += month(y, m); if (++m > 12) { m = 1; y++; } }
      html += '</div><div class="rp-legend"><span><i class="free"></i>خالی (قیمت شب)</span><span><i class="hot"></i>گران‌تر</span><span><i class="booked"></i>پر / غیرقابل رزرو</span></div><div class="rp-foot"><button type="button" class="btn btn-sm btn-ghost" data-clear>پاک کردن</button><button type="button" class="btn btn-sm" data-close>تأیید</button></div>';
      pop.innerHTML = html;
    }
    const open = () => { render(); pop.classList.add('open'); };
    const close = () => pop.classList.remove('open');
    $('.rp-field', root).addEventListener('click', () => pop.classList.contains('open') ? close() : open());
    pop.addEventListener('click', e => {
      const t = e.target;
      if (t.dataset.nav) { vm += +t.dataset.nav; if (vm > 12) { vm = 1; vy++; } if (vm < 1) { vm = 12; vy--; } render(); return; }
      if (t.hasAttribute('data-clear')) { start = end = null; sync(); render(); return; }
      if (t.hasAttribute('data-close')) { close(); return; }
      if (t.dataset.iso) {
        const iso = t.dataset.iso;
        if (!start || end || iso <= start) { start = iso; end = null; } else { end = iso; }
        sync(); render(); if (end) setTimeout(close, 250);
      }
    });
    document.addEventListener('click', e => { if (!e.composedPath().includes(root)) close(); });
    window.addEventListener('resize', () => pop.classList.contains('open') && render());
    root._rp = { set(a, b) { start = a; end = b; if (a) [vy, vm] = Jalali.parseISO(a).slice(0, 2); sync(); if (pop.classList.contains('open')) render(); } };
    sync();
  }
  $$('[data-rangepicker]').forEach(RangePicker);

  /* ---------- پیش‌تنظیم‌های تاریخ و پاسخ‌های سریع ---------- */
  document.addEventListener('click', e => {
    const p = e.target.closest('[data-preset]'); if (p) {
      const form = p.closest('form'), rp = form && form.querySelector('[data-rangepicker]'); if (!rp || !rp._rp) return;
      const d = new Date(); d.setHours(12, 0, 0, 0); const dow = d.getDay(); // 0=یکشنبه
      const toThu = (4 - dow + 7) % 7; const add = n => { const x = new Date(d); x.setDate(x.getDate() + n); return x; };
      const iso = x => x.getFullYear() + '-' + Jalali.pad(x.getMonth() + 1) + '-' + Jalali.pad(x.getDate());
      const k = p.dataset.preset; let a, b;
      if (k === 'weekend') { a = add(toThu === 0 && dow === 4 ? 0 : toThu); b = new Date(a); b.setDate(b.getDate() + 2); }
      else if (k === 'weekend2') { a = add((toThu || 7) + 7 - (dow === 4 ? 7 : 0)); b = new Date(a); b.setDate(b.getDate() + 2); }
      else if (k === 'midweek') { a = add(((6 - dow + 7) % 7) || 7); b = new Date(a); b.setDate(b.getDate() + 3); }
      else { a = add(1); b = add(4); }
      rp._rp.set(iso(a), iso(b));
      document.querySelectorAll('[data-preset]').forEach(x => x.classList.toggle('on', x === p));
    }
    const q = e.target.closest('[data-qr]'); if (q) { const f = q.closest('.card').querySelector('input[name=body]'); if (f) { f.value = q.dataset.qr; f.focus(); } }
  });

  /* ---------- ویجت رزرو (محاسبه‌ی قیمت زنده) ---------- */
  const bw = $('#booking-form');
  if (bw) {
    const q = $('#quote'), submit = $('#book-submit'), url = bw.dataset.quote;
    let timer;
    const run = () => {
      clearTimeout(timer);
      timer = setTimeout(async () => {
        const fd = new FormData(bw), p = new URLSearchParams();
        ['check_in', 'check_out', 'adults', 'children', 'coupon'].forEach(k => p.set(k, fd.get(k) || ''));
        if (!p.get('check_in') || !p.get('check_out')) { q.innerHTML = '<div class="muted small center">برای مشاهده‌ی قیمت نهایی، تاریخ ورود و خروج را انتخاب کنید.</div>'; submit.disabled = true; return; }
        q.innerHTML = '<div class="muted small center">در حال محاسبه…</div>';
        try {
          const r = await fetch(url + '?' + p.toString(), { headers: { Accept: 'application/json' } });
          const j = await r.json();
          if (j.ok) { q.innerHTML = j.html; submit.disabled = false; $$('.mb-total').forEach(e => e.textContent = Jalali.fa(j.total.toLocaleString('en-US')).replace(/,/g, '٬') + ' تومان'); }
          else { q.innerHTML = '<div class="alert bad" style="margin:0">' + j.error + '</div>'; submit.disabled = true; }
        } catch (_) { q.innerHTML = '<div class="alert bad" style="margin:0">خطا در محاسبه‌ی قیمت.</div>'; }
      }, 250);
    };
    bw.addEventListener('rp:change', run); bw.addEventListener('change', run); $('[name=coupon]', bw) && $('[name=coupon]', bw).addEventListener('input', run);
    run();
    $$('[data-scroll-book]').forEach(b => b.addEventListener('click', () => bw.scrollIntoView({ behavior: 'smooth', block: 'center' })));
  }

  /* ---------- تقویم میزبان ---------- */
  const cal = $('#host-cal');
  if (cal) {
    const bar = $('#cal-bar'), cnt = $('#cal-count'), box = $('#cal-dates');
    const sel = new Set();
    const refresh = () => {
      box.innerHTML = ''; sel.forEach(d => { const i = document.createElement('input'); i.type = 'hidden'; i.name = 'dates[]'; i.value = d; box.appendChild(i); });
      cnt.textContent = Jalali.fa(sel.size); bar.classList.toggle('show', sel.size > 0);
    };
    cal.addEventListener('click', e => {
      const c = e.target.closest('.cal-cell[data-date]'); if (!c || c.classList.contains('past') || c.classList.contains('booked')) return;
      const d = c.dataset.date; if (sel.has(d)) { sel.delete(d); c.classList.remove('sel'); } else { sel.add(d); c.classList.add('sel'); }
      refresh();
    });
    $('#cal-clear') && $('#cal-clear').addEventListener('click', () => { sel.clear(); $$('.cal-cell.sel').forEach(c => c.classList.remove('sel')); refresh(); });
    $$('[data-cal-action]').forEach(b => b.addEventListener('click', () => { $('#cal-action').value = b.dataset.calAction; }));
  }
})();

/* ---------- موج (ripple) روی دکمه‌های شیشه‌ای ---------- */
document.addEventListener('pointerdown', e => {
  const b = e.target.closest('.btn'); if (!b || b.disabled) return;
  const r = b.getBoundingClientRect(), s = Math.max(r.width, r.height), o = document.createElement('span');
  o.className = 'btn-ripple'; o.style.cssText = `width:${s}px;height:${s}px;left:${e.clientX - r.left - s / 2}px;top:${e.clientY - r.top - s / 2}px`;
  b.appendChild(o); setTimeout(() => o.remove(), 650);
});

/* ---------- کپی در کلیپ‌بورد ---------- */
document.addEventListener('click', async e => {
  const b = e.target.closest('[data-copy]'); if (!b) return;
  const el = document.querySelector(b.dataset.copy); if (!el) return;
  try { await navigator.clipboard.writeText(el.textContent.trim()); } catch (_) { const r = document.createRange(); r.selectNode(el); getSelection().removeAllRanges(); getSelection().addRange(r); document.execCommand('copy'); }
  const t = b.innerHTML; b.textContent = 'کپی شد'; setTimeout(() => b.innerHTML = t, 1600);
});

/* ---------- شمارش معکوس فارسی (حلقه‌ی پیشرفت) ---------- */
(function () {
  const fa = s => String(s).replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
  document.querySelectorAll('[data-cd]').forEach(el => {
    const end = new Date(el.dataset.cd).getTime(), total = +el.dataset.total || 1800;
    const t = el.querySelector('[data-cd-t]'), fg = el.querySelector('.fg'), C = 327;
    const skew = (+el.dataset.left * 1000) - (end - Date.now());   // اختلاف ساعت سرور و مرورگر
    const tick = () => {
      const left = Math.max(0, Math.round((end - Date.now() + skew) / 1000));
      t.textContent = fa(String(Math.floor(left / 60)).padStart(2, '0') + ':' + String(left % 60).padStart(2, '0'));
      if (fg) fg.style.strokeDashoffset = C * (1 - Math.min(1, left / total));
      el.classList.toggle('low', left < 300); el.classList.toggle('done', left === 0);
      if (left === 0) { clearInterval(iv); setTimeout(() => location.reload(), 2500); }
    };
    const iv = setInterval(tick, 1000); tick();
  });
})();

/* ---------- اعلان‌های زنده: هر ۲۵ ثانیه اعلان تازه می‌گیرد و نشان می‌دهد ---------- */
(function () {
  var m = document.querySelector('meta[name=notice-last]');
  if (!m || !window.fetch) return;
  var last = parseInt(m.content || '0', 10), busy = false;
  var inApp = /MijaApp\//.test(navigator.userAgent);
  var fa = function (n) { return String(n).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); };
  function box() {
    var b = document.getElementById('live-toasts');
    if (!b) { b = document.createElement('div'); b.id = 'live-toasts'; b.className = 'live-toasts'; document.body.appendChild(b); }
    return b;
  }
  function toast(n) {
    var a = document.createElement('a'); a.className = 'live-toast'; a.href = n.url || '/notices';
    var t = document.createElement('b'); t.textContent = n.title; a.appendChild(t);
    if (n.body) { var s = document.createElement('span'); s.textContent = n.body; a.appendChild(s); }
    box().appendChild(a);
    setTimeout(function () { a.classList.add('out'); setTimeout(function () { a.remove(); }, 400); }, 7000);
    try { if (!inApp && window.Notification && Notification.permission === 'granted' && document.hidden) { var x = new Notification(n.title, { body: n.body || '', icon: '/icons/icon-192.png', lang: 'fa', dir: 'rtl' }); x.onclick = function () { location.href = a.href; }; } } catch (e) {}
  }
  function badge(c) {
    var l = document.querySelector('a.top-btn[href$="/notices"]'); if (!l) return;
    var d = l.querySelector('.dot'); if (!c) { d && d.remove(); return; }
    if (!d) { d = document.createElement('span'); d.className = 'dot'; l.appendChild(d); } d.textContent = fa(c);
  }
  function poll() {
    if (busy || document.hidden) return; busy = true;
    fetch('/api/notices?after=' + last, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (j) { if (!j) return; (j.items || []).forEach(toast); if (j.last_id) last = Math.max(last, j.last_id); badge(j.unread); })
      .catch(function () {}).then(function () { busy = false; });
  }
  setInterval(poll, 25000); document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });
  var en = document.getElementById('enable-browser-notify');
  if (en) { if (!window.Notification || Notification.permission === 'granted') en.hidden = true; en.addEventListener('click', function () { Notification.requestPermission().then(function () { en.hidden = true; }); }); }
})();

/* اینماد: اگر تصویر نماد از سایت اینماد بارگذاری نشد، لینک متنی نشان بده تا نماد همیشه قابل کلیک باشد */
(function () {
  var box = document.getElementById('enamad-box'); if (!box) return;
  var img = box.querySelector('img'), a = box.querySelector('a'); if (!img || !a) return;
  function fb() { if (box.querySelector('.enamad-fb')) return; img.style.display = 'none'; var l = document.createElement('a'); l.className = 'enamad-fb'; l.href = a.href; l.target = '_blank'; l.rel = 'noopener'; l.textContent = '✔ نماد اعتماد الکترونیکی (اینماد) — برای تأیید کلیک کنید'; box.appendChild(l); }
  img.addEventListener('error', fb);
  setTimeout(function () { if (!img.complete || img.naturalWidth === 0) fb(); }, 6000);
})();

/* ---------- کارت بانکی کیف پول: شمارش انیمیشنی، شیب سه‌بعدی، انتخاب سریع مبلغ ---------- */
(function () {
  var fa = function (n) { return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '٬').replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); };
  document.querySelectorAll('.atm-card .count').forEach(function (el) {
    var to = parseInt(el.getAttribute('data-to') || '0', 10); if (!to || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    var t0 = null, dur = 1100; el.textContent = fa(0);
    (function step(ts) { if (!t0) t0 = ts; var p = Math.min(1, (ts - t0) / dur), e = 1 - Math.pow(1 - p, 3); el.textContent = fa(Math.round(to * e)); if (p < 1) requestAnimationFrame(step); })(performance.now());
  });
  document.querySelectorAll('[data-tilt]').forEach(function (c) {
    if (matchMedia('(hover: none)').matches) return;
    c.addEventListener('pointermove', function (e) { var r = c.getBoundingClientRect(), x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5; c.style.transform = 'perspective(900px) rotateY(' + (-x * 8) + 'deg) rotateX(' + (y * 8) + 'deg)'; });
    c.addEventListener('pointerleave', function () { c.style.transform = ''; });
  });
  document.querySelectorAll('.atm-chips').forEach(function (box) {
    var inp = document.getElementById('po-amt'); if (!inp) return;
    var max = parseInt(box.getAttribute('data-max') || '0', 10);
    box.querySelectorAll('button').forEach(function (b) {
      b.addEventListener('click', function () {
        var v = b.dataset.v ? parseInt(b.dataset.v, 10) : Math.floor(max * parseInt(b.dataset.pc, 10) / 100);
        inp.value = v || ''; inp.dispatchEvent(new Event('input'));
        box.querySelectorAll('button').forEach(function (x) { x.classList.toggle('on', x === b); });
      });
    });
  });
  var f = document.querySelector('form.atm-form');
  if (f) f.addEventListener('submit', function () { var g = f.querySelector('.atm-go'); if (g) g.classList.add('busy'); });
})();
