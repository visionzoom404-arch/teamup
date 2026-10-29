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
  window.addEventListener('beforeinstallprompt', e => {
    e.preventDefault(); deferred = e;
    try { if (localStorage.getItem('mija-install-x')) return; } catch (_) {}
    bar && bar.classList.add('show');
  });
  $('#install-btn') && $('#install-btn').addEventListener('click', async () => {
    if (!deferred) return; deferred.prompt(); await deferred.userChoice; deferred = null; bar.classList.remove('show');
  });
  $('#install-x') && $('#install-x').addEventListener('click', () => {
    bar.classList.remove('show'); try { localStorage.setItem('mija-install-x', '1'); } catch (_) {}
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

  /* ---------- گالری ---------- */
  const lb = $('#lightbox');
  $$('[data-lightbox]').forEach(a => a.addEventListener('click', e => {
    e.preventDefault(); if (!lb) return; $('img', lb).src = a.getAttribute('href'); lb.classList.add('open');
  }));
  lb && lb.addEventListener('click', () => lb.classList.remove('open'));

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
        h += '<button type="button" class="' + cls.join(' ') + '" data-iso="' + iso + '"' + (dis ? ' disabled' : '') + '>' + Jalali.fa(d) + '</button>';
      }
      return '<div><div class="rp-head"><span>' + Jalali.months[jm - 1] + ' ' + Jalali.fa(jy) + '</span></div>' + h + '</div></div>';
    }
    function render() {
      const n = narrow() ? 1 : 2;
      let html = '<div class="rp-head" style="margin-bottom:6px"><button type="button" class="rp-nav" data-nav="1" aria-label="ماه بعد">›</button><span class="muted small">' + (start && !end ? 'تاریخ خروج را انتخاب کنید' : 'تاریخ ورود را انتخاب کنید') + '</span><button type="button" class="rp-nav" data-nav="-1" aria-label="ماه قبل">‹</button></div><div class="rp-months">';
      let y = vy, m = vm;
      for (let i = 0; i < n; i++) { html += month(y, m); if (++m > 12) { m = 1; y++; } }
      html += '</div><div class="rp-foot"><button type="button" class="btn btn-sm btn-ghost" data-clear>پاک کردن</button><button type="button" class="btn btn-sm" data-close>تأیید</button></div>';
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
    sync();
  }
  $$('[data-rangepicker]').forEach(RangePicker);

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
