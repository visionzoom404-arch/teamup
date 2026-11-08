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
    const any = c.hasAttribute('data-any'); // مقدار صفر یعنی «هر تعداد»
    const set = v => { v = Math.max(min, Math.min(max, v || 0)); inp.value = any && v === 0 ? '' : v; out.textContent = any && v === 0 ? 'هر تعداد' : Jalali.fa(v); inp.dispatchEvent(new Event('change', { bubbles: true })); };
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

/* ---------- حالت روشن / تیره (با انیمیشن حرکت به سمت خورشید / ماه) ---------- */
(function () {
  const root = document.documentElement;
  const SUN = [0.8217, 0.435], MOON = [0.8247, 0.3686]; // مختصات خورشید/ماه در عکس‌های پیش‌فرض (نسبت به عکس)
  const apply = t => {
    root.setAttribute('data-theme', t);
    const m = document.querySelector('meta[name=theme-color]'); if (m) m.setAttribute('content', t === 'dark' ? '#17120D' : '#E8860C');
    try { localStorage.setItem('mija-theme', t); } catch (e) { }
    document.dispatchEvent(new CustomEvent('mija:theme', { detail: t }));
  };
  // عکس هرویِ حالت مقابل را زودتر بارگیری کن تا هنگام انیمیشن آماده باشد
  const preloadHero = () => document.querySelectorAll('.hx-night img, img.hx-night, .hx-day img, img.hx-day').forEach(i => { if (i.loading === 'lazy') i.loading = 'eager'; });
  if (document.querySelector('.hx-night')) (window.requestIdleCallback || (f => setTimeout(f, 1500)))(preloadHero);

  // نقطه‌ی هدف: اگر عکس اصلیِ صفحه‌ی اول روی صفحه دیده می‌شود و خورشید/ماه در کادر است، همان؛
  // در غیر این صورت (صفحه‌های دیگر یا وقتی بنر اسکرول شده و رفته) خودِ دکمه‌ی تغییر حالت
  const target = (toDark, btn) => {
    const vw = innerWidth, vh = innerHeight;
    const img = [...document.querySelectorAll('.hx-img')].find(i => i.offsetParent !== null);
    if (img) {
      const fx = (img.getAttribute('data-fx') || (toDark ? SUN : MOON).join(',')).split(',').map(Number);
      const r = img.getBoundingClientRect(), iw = img.naturalWidth || r.width, ih = img.naturalHeight || r.height;
      if (r.width > 0 && r.bottom > 0 && r.top < vh) {
        const k = Math.max(r.width / iw, r.height / ih), py = parseFloat((getComputedStyle(img).objectPosition.split(' ')[1]) || '50') / 100;
        const ox = (r.width - iw * k) / 2, oy = (r.height - ih * k) * (isNaN(py) ? 0.5 : py);
        const x = r.left + ox + fx[0] * iw * k, y = r.top + oy + fx[1] * ih * k;
        const hdr = document.querySelector('.topbar'), top = hdr ? hdr.getBoundingClientRect().bottom + 8 : 70;
        // خورشید/ماه باید کاملاً دیده شود (نه زیر هدر، نه خارج از صفحه، نه زیر نوار پایین)
        if (x > 24 && x < vw - 24 && y > top + 16 && y < vh - 90) return [x, y];
      }
    }
    if (btn) { const b = btn.getBoundingClientRect(); return [b.left + b.width / 2, b.top + b.height / 2]; }
    return [vw - 60, 40];
  };

  let busy = false;
  const toggle = async btn => {
    if (busy) return; busy = true;
    const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark', toDark = next === 'dark';
    const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduce || !document.body) { apply(next); busy = false; return; }
    preloadHero();
    const [sx, sy] = target(toDark, btn), vw = innerWidth, vh = innerHeight;
    const R = Math.hypot(Math.max(sx, vw - sx), Math.max(sy, vh - sy)) + 40;
    const D = window.__themeDur || 1100, EASE = 'cubic-bezier(.65,0,.3,1)';
    // کره‌ی خورشید/ماه (خودش یک لایه‌ی جدا در انیمیشن است)
    const orb = document.createElement('div'); orb.id = 'theme-orb'; orb.className = toDark ? 'sun' : 'moon'; orb.setAttribute('aria-hidden', 'true');
    orb.style.cssText = `left:${sx - 36}px;top:${sy - 36}px;width:72px;height:72px`;
    document.body.appendChild(orb);
    // اگر عکس مقصد هنوز نیامده، کوتاه صبر کن
    const want = document.querySelector(toDark ? '.hx-night img, img.hx-night' : '.hx-day img, img.hx-day');
    if (want && !want.complete) { try { await Promise.race([want.decode(), new Promise(r => setTimeout(r, 700))]); } catch (e) { } }
    const done = () => { orb.remove(); root.classList.remove('theme-vt'); busy = false; };
    if (typeof document.startViewTransition === 'function') {
      root.classList.add('theme-vt');
      const vt = document.startViewTransition(() => {
        apply(next);
        orb.style.cssText = `left:${sx - R}px;top:${sy - R}px;width:${2 * R}px;height:${2 * R}px;opacity:0`;
      });
      vt.ready.then(() => {
        const o = `${sx}px ${sy}px`;
        root.animate({ clipPath: [`circle(0px at ${o})`, `circle(${R}px at ${o})`] }, { duration: D, easing: EASE, fill: 'both', pseudoElement: '::view-transition-new(root)' });
        root.animate([
          { transform: 'scale(1)', transformOrigin: o, filter: 'brightness(1) saturate(1)', opacity: 1 },
          { transform: 'scale(1.5)', transformOrigin: o, filter: toDark ? 'brightness(1.25) saturate(1.3)' : 'brightness(.85)', opacity: 1, offset: .7 },
          { transform: 'scale(2.4)', transformOrigin: o, filter: toDark ? 'brightness(1.6) blur(8px)' : 'brightness(.6) blur(8px)', opacity: 0 }
        ], { duration: D, easing: EASE, fill: 'both', pseudoElement: '::view-transition-old(root)' });
        const g = { duration: D, easing: EASE, fill: 'both' };
        root.animate({ opacity: [1, 1, 0], offset: [0, .55, 1] }, { ...g, pseudoElement: '::view-transition-new(theme-orb)' });
        root.animate({ opacity: [1, 1, 0], offset: [0, .55, 1] }, { ...g, pseudoElement: '::view-transition-old(theme-orb)' });
      }).catch(() => { });
      vt.finished.then(done, done);
    } else {
      // پشتیبان: دایره‌ی رنگی از محل خورشید/ماه باز می‌شود، حالت عوض می‌شود، سپس محو می‌شود
      const veil = document.createElement('div'); veil.className = 'theme-veil ' + (toDark ? 'to-dark' : 'to-light'); veil.setAttribute('aria-hidden', 'true');
      document.body.appendChild(veil);
      const a = veil.animate({ clipPath: [`circle(0px at ${sx}px ${sy}px)`, `circle(${R}px at ${sx}px ${sy}px)`] }, { duration: D * .55, easing: EASE, fill: 'forwards' });
      a.finished.then(() => {
        apply(next); orb.remove();
        return veil.animate({ opacity: [1, 0] }, { duration: D * .45, easing: 'ease-out', fill: 'forwards' }).finished;
      }).then(() => { veil.remove(); done(); }, () => { veil.remove(); done(); });
    }
  };
  document.addEventListener('click', e => { const b = e.target.closest('[data-theme-toggle]'); if (b) toggle(b); });
  // اگر کاربر دستی انتخاب نکرده، با تغییر حالت سیستم هماهنگ شود
  try {
    matchMedia('(prefers-color-scheme: dark)').addEventListener('change', ev => {
      let saved = null; try { saved = localStorage.getItem('mija-theme'); } catch (e) { }
      if (!saved) { root.setAttribute('data-theme', ev.matches ? 'dark' : 'light'); const m = document.querySelector('meta[name=theme-color]'); if (m) m.setAttribute('content', ev.matches ? '#17120D' : '#E8860C'); }
    });
  } catch (e) { }
})();

/* ---------- لیست‌های کشویی شیشه‌ای (به‌جای منوی پیش‌فرض مرورگر) ---------- */
(function () {
  const SEL = '.searchbar select,.filters select,select[data-glass]';
  let pop = null, curSel = null;
  const off = () => { document.removeEventListener('keydown', onKey, true); document.removeEventListener('mousedown', onOut, true); document.removeEventListener('touchstart', onOut, true); window.removeEventListener('resize', close); window.removeEventListener('scroll', onScroll, true); };
  const close = (refocus) => { if (!pop) return; pop.remove(); pop = null; if (curSel) { curSel.parentElement.classList.remove('gs-open'); if (refocus) curSel.focus({ preventScroll: true }); curSel = null; } off(); };
  const onOut = e => { if (pop && !pop.contains(e.target) && !(curSel && curSel.parentElement.contains(e.target))) close(); };
  const onScroll = e => { if (pop && !pop.contains(e.target)) close(); };
  const onKey = e => {
    if (!pop) return;
    const items = [...pop.querySelectorAll('.gs-opt')], i = items.indexOf(document.activeElement);
    if (e.key === 'Escape') { e.preventDefault(); close(true); }
    else if (e.key === 'ArrowDown') { e.preventDefault(); (items[i + 1] || items[0]).focus(); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); (items[i - 1] || items[items.length - 1]).focus(); }
    else if (e.key === 'Tab') { close(); }
  };
  const open = sel => {
    const same = curSel === sel; close(); if (same) return;
    curSel = sel;
    const cell = sel.parentElement; cell.classList.add('gs-open');
    pop = document.createElement('div'); pop.className = 'gs-pop'; pop.setAttribute('role', 'listbox'); pop.setAttribute('dir', 'rtl');
    [...sel.options].forEach((o, i) => {
      if (o.hidden) return;
      const b = document.createElement('button'); b.type = 'button'; b.className = 'gs-opt' + (o.selected ? ' on' : ''); b.setAttribute('role', 'option'); b.setAttribute('aria-selected', o.selected ? 'true' : 'false');
      b.textContent = o.textContent.trim();
      b.addEventListener('click', () => {
        sel.selectedIndex = i;
        sel.dispatchEvent(new Event('input', { bubbles: true })); sel.dispatchEvent(new Event('change', { bubbles: true }));
        close(true);
      });
      pop.appendChild(b);
    });
    document.body.appendChild(pop);
    const multi = cell.querySelectorAll('select').length > 1, r = (multi ? sel : cell).getBoundingClientRect(), w = Math.max(r.width, 210), vh = innerHeight, vw = innerWidth;
    pop.style.width = w + 'px';
    const h = Math.min(pop.scrollHeight, 300), below = vh - r.bottom - 14, above = r.top - 14;
    const up = below < Math.min(h, 220) && above > below;
    pop.style.maxHeight = Math.max(140, Math.min(300, up ? above : below)) + 'px';
    const ph = Math.min(pop.scrollHeight, parseFloat(pop.style.maxHeight));
    pop.style.top = (up ? r.top - ph - 8 : r.bottom + 8) + 'px';
    pop.style.left = Math.min(Math.max(8, r.right - w), vw - w - 8) + 'px'; // هم‌تراز با لبه‌ی راست (راست‌به‌چپ)
    const on = pop.querySelector('.gs-opt.on') || pop.querySelector('.gs-opt'); if (on) { on.focus({ preventScroll: true }); pop.scrollTop = Math.max(0, on.offsetTop - pop.clientHeight / 2 + on.offsetHeight / 2); }
    document.addEventListener('keydown', onKey, true); document.addEventListener('mousedown', onOut, true); document.addEventListener('touchstart', onOut, true);
    window.addEventListener('resize', close); window.addEventListener('scroll', onScroll, true);
  };
  const init = () => document.querySelectorAll(SEL).forEach(sel => {
    if (sel.multiple || sel.size > 1 || sel.dataset.gsInit) return;
    sel.dataset.gsInit = '1'; sel.classList.add('gs-native'); sel.parentElement.classList.add('gs-cell');
    const par = sel.parentElement;
    if (!par.dataset.gsBound) {
      par.dataset.gsBound = '1';
      par.addEventListener('click', e => {
        if (e.target.closest('a,button,input:not([type=hidden]),textarea')) return;
        const list = [...par.querySelectorAll('select.gs-native')]; if (!list.length) return;
        const hit = list.find(s => { const r = s.getBoundingClientRect(); return e.clientY >= r.top - 6 && e.clientY <= r.bottom + 6; });
        if (!hit && list.length > 1) return;
        e.preventDefault(); open(hit || list[0]);
      });
    }
    sel.addEventListener('keydown', e => { if (['Enter', ' ', 'ArrowDown', 'ArrowUp'].includes(e.key)) { e.preventDefault(); open(sel); } });
  });
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();

/* ---------- خطاهای فرم: فارسی، قابل کلیک، با علامت‌گذاری خودکار فیلتر ---------- */
(function () {
  const faN = v => (window.Jalali && Jalali.fa) ? Jalali.fa(v) : String(v);
  const labelOf = el => {
    let t = '';
    const byFor = el.id && document.querySelector('label[for="' + el.id + '"]');
    if (byFor) t = byFor.textContent;
    else { const f = el.closest('.field,.fl'); const l = f && f.querySelector('label'); if (l) t = l.textContent; }
    if (!t) t = el.getAttribute('aria-label') || el.getAttribute('placeholder') || el.title || '';
    return t.replace(/\(.*?\)/g, '').replace(/[—–-].*$/, '').replace(/[:*٪]/g, '').replace(/\s+/g, ' ').trim();
  };
  // پیام‌های فارسی به‌جای حباب انگلیسی/ناخوانای مرورگر
  const msgFor = el => {
    const v = el.validity, L = labelOf(el), q = L ? '«' + L + '»' : 'این فیلد';
    if (v.valueMissing) return el.type === 'checkbox' || el.type === 'radio' ? 'برای ادامه، ' + q + ' را تأیید یا انتخاب کنید.' : (el.tagName === 'SELECT' || el.type === 'file' ? q + ' را انتخاب کنید.' : q + ' را وارد کنید.');
    if (v.typeMismatch) return el.type === 'email' ? 'نشانی ایمیل معتبر نیست؛ مثل name@example.com' : (el.type === 'url' ? 'آدرس وب معتبر نیست (با https:// شروع شود).' : q + ' معتبر نیست.');
    if (v.tooShort) return q + ' باید حداقل ' + faN(el.minLength) + ' کاراکتر باشد (الان ' + faN(el.value.length) + ' کاراکتر نوشته‌اید).';
    if (v.tooLong) return q + ' نباید بیشتر از ' + faN(el.maxLength) + ' کاراکتر باشد.';
    if (v.rangeUnderflow) return q + ' نباید کمتر از ' + faN(el.min) + ' باشد.';
    if (v.rangeOverflow) return q + ' نباید بیشتر از ' + faN(el.max) + ' باشد.';
    if (v.stepMismatch) return q + ' باید عدد صحیح (بدون اعشار) باشد.';
    if (v.badInput) return q + ' را فقط با عدد وارد کنید.';
    if (v.patternMismatch) return el.title || ('قالب ' + q + ' درست نیست.');
    return '';
  };
  document.addEventListener('invalid', e => {
    const el = e.target; if (!el.setCustomValidity || el.closest('[data-native-validation]')) return;
    el.setCustomValidity(''); const m = msgFor(el); if (m) el.setCustomValidity(m);
    if (window.pfGoto) window.pfGoto(el); // اگر فیلد در مرحله‌ی دیگرِ فرم است، همان مرحله را باز کن
    markField(el, m || el.validationMessage);
  }, true);
  document.addEventListener('input', e => { const el = e.target; if (el.setCustomValidity) el.setCustomValidity(''); unmark(el); }, true);
  document.addEventListener('change', e => { const el = e.target; if (el.setCustomValidity) el.setCustomValidity(''); unmark(el); }, true);

  const wrapOf = el => el.closest('.field,.fl') || el.parentElement;
  const markField = (el, msg) => {
    const w = wrapOf(el); if (!w) return;
    w.classList.add('has-err'); el.setAttribute('aria-invalid', 'true');
    let m = w.querySelector('.msg-e');
    if (!m) { m = document.createElement('div'); m.className = 'msg-e'; m.setAttribute('data-auto', '1'); w.appendChild(m); }
    if (m.getAttribute('data-auto') || !m.textContent.trim()) m.textContent = msg;
  };
  const unmark = el => {
    const w = wrapOf(el); if (!w || !w.classList.contains('has-err')) return;
    w.classList.remove('has-err'); el.removeAttribute('aria-invalid');
    const m = w.querySelector('.msg-e'); if (m) m.remove();
  };

  // پیدا کردن فیلتر مربوط به کلید خطای سرور (مثل images.2 یا price_dow.3)
  const find = key => {
    const parts = key.split('.'), base = parts[0], br = base + parts.slice(1).map(p => '[' + p + ']').join('');
    const q = s => { try { return document.querySelector(s); } catch (e) { return null; } };
    let el = q('[name="' + key + '"]') || q('[name="' + br + '"]') || q('[name="' + base + '[]"]') || q('[name^="' + base + '["]') || q('#' + CSS.escape(key));
    if (!el && (key === 'lat' || key === 'lng')) el = q('[data-map-pick]');
    return el;
  };
  const visible = el => {
    if (el.type === 'hidden' || el.offsetParent === null) { const w = wrapOf(el); return (w && w.offsetParent !== null) ? w : (el.closest('.card,.pf-step') || el.parentElement); }
    return el;
  };
  const pulse = t => { t.classList.remove('err-pulse'); void t.offsetWidth; t.classList.add('err-pulse'); setTimeout(() => t.classList.remove('err-pulse'), 1600); };
  const go = key => {
    const el = find(key); const box = document.getElementById('errbox');
    if (!el) { box && box.scrollIntoView({ behavior: 'smooth', block: 'center' }); return; }
    if (window.pfGoto) window.pfGoto(el);
    requestAnimationFrame(() => {
      const t = visible(el);
      t.scrollIntoView({ behavior: 'smooth', block: 'center' });
      setTimeout(() => { if (el.focus && el.type !== 'hidden' && el.offsetParent !== null) { try { el.focus({ preventScroll: true }); } catch (e) { } } pulse(t); }, 380);
    });
  };
  document.addEventListener('click', e => {
    const a = e.target.closest('#errbox [data-err-key]'); if (!a) return;
    e.preventDefault(); go(a.getAttribute('data-err-key'));
  });
  // با باز شدن صفحه، فیلدهای دارای خطا علامت بخورند (و در موبایل مرحله‌ی اولین خطا باز شود)
  const boot = () => {
    const box = document.getElementById('errbox'); if (!box) return;
    let keys = []; try { keys = JSON.parse(box.getAttribute('data-keys') || '[]'); } catch (e) { }
    const msgs = {}; box.querySelectorAll('[data-err-key]').forEach(a => { const k = a.getAttribute('data-err-key'); (msgs[k] = msgs[k] || []).push(a.textContent.trim()); });
    let first = null;
    keys.forEach(k => { const el = find(k); if (!el) return; first = first || el; const w = wrapOf(visible(el)) || visible(el); if (!w) return; w.classList.add('has-err'); if (el.setAttribute) el.setAttribute('aria-invalid', 'true'); if (!w.querySelector('.msg-e')) { const m = document.createElement('div'); m.className = 'msg-e'; m.setAttribute('data-auto', '1'); m.textContent = msgs[k][0]; w.appendChild(m); } });
    if (first && window.pfGoto) window.pfGoto(first);
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => setTimeout(boot, 0)); else setTimeout(boot, 0);
})();
