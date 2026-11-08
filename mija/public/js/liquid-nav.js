/* منوی چسبان پایین «شیشه‌ی مایع» — عدسی انتخاب، کشیدن انگشت، جمع‌شدن هنگام اسکرول */
(function () {
  'use strict';
  const nav = document.querySelector('nav.bottomnav');
  const ul = nav && nav.querySelector('ul');
  if (!nav || !ul) return;
  const items = [...ul.children].filter(li => li.tagName === 'LI');
  if (!items.length) return;

  const mq = matchMedia('(max-width:860px)');
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const KEY = 'mija-tab:' + (nav.getAttribute('aria-label') || '') + ':' + items.length;
  const store = { get() { try { return sessionStorage.getItem(KEY); } catch (e) { return null; } }, set(v) { try { sessionStorage.setItem(KEY, String(v)); } catch (e) { } } };
  const buzz = (ms) => { try { navigator.vibrate && navigator.vibrate(ms); } catch (e) { } };

  nav.classList.add('lg-nav');
  const lens = document.createElement('span');
  lens.className = 'lg-lens'; lens.setAttribute('aria-hidden', 'true');
  nav.insertBefore(lens, ul);
  const edge = document.createElement('div'); edge.className = 'lg-edge'; edge.setAttribute('aria-hidden', 'true'); document.body.appendChild(edge);

  // تب فعال (اگر صفحه در هیچ تبی نیست، اولین تب برای حالت جمع‌شده نشان داده می‌شود)
  const linkOf = li => li.querySelector('a,button');
  let active = items.findIndex(li => { const a = li.querySelector('a'); return a && a.classList.contains('on'); });
  const hasActive = active >= 0; if (!hasActive) active = 0;
  nav.classList.toggle('no-active', !hasActive);
  items.forEach((li, i) => { li.classList.toggle('is-active', i === active); const a = li.querySelector('a'); if (a && i === active && hasActive) a.setAttribute('aria-current', 'page'); });

  let hover = active, selected = null, stretchT = 0;
  const place = (i, anim) => {
    const li = items[i]; if (!li) return;
    const nr = nav.getBoundingClientRect(), lr = li.getBoundingClientRect();
    if (!lr.width) return;
    if (!anim) lens.classList.add('no-anim');
    lens.style.left = (lr.left - nr.left) + 'px'; lens.style.width = lr.width + 'px';
    if (!anim) { void lens.offsetWidth; lens.classList.remove('no-anim'); }
    else if (!reduce) { lens.classList.add('is-stretch'); clearTimeout(stretchT); stretchT = setTimeout(() => lens.classList.remove('is-stretch'), 190); }
  };
  const setHover = (i, anim) => {
    if (i === hover && anim !== 'force') { return; }
    items.forEach((li, k) => li.classList.toggle('is-hover', k === i && nav.classList.contains('is-press')));
    hover = i; place(i, true);
  };

  // موقعیت اولیه: از تبِ صفحه‌ی قبل بلغزد (پیوستگی بین صفحه‌ها)
  const init = () => {
    const prev = parseInt(store.get(), 10);
    if (hasActive && !isNaN(prev) && prev !== active && prev >= 0 && prev < items.length && !reduce && mq.matches) {
      place(prev, false); hover = prev;
      requestAnimationFrame(() => requestAnimationFrame(() => { hover = active; place(active, true); }));
    } else { place(active, false); }
    nav.classList.add('lg-ready');
  };
  (document.fonts && document.fonts.ready ? document.fonts.ready : Promise.resolve()).then(() => requestAnimationFrame(init));

  // با تغییر اندازه (چرخش صفحه، جمع/باز شدن) عدسی دنبال تب بماند
  if ('ResizeObserver' in window) new ResizeObserver(() => { if (!nav.classList.contains('is-press')) place(hover, false); }).observe(nav);
  addEventListener('resize', () => { if (!mq.matches) setMin(false); place(hover, false); }, { passive: true });
  addEventListener('pageshow', e => { if (e.persisted) { selected = null; hover = active; place(active, false); setMin(false); nav.classList.remove('is-press'); } });
  addEventListener('pagehide', () => store.set(selected !== null ? selected : active));

  // ---------- جمع‌شدن / بازشدن ----------
  const setMin = v => {
    if (v && (nav.classList.contains('is-press') || !mq.matches)) return;
    if (nav.classList.contains('is-min') === v) return;
    nav.classList.toggle('is-min', v);
    if (v) { hover = active; items.forEach(li => li.classList.remove('is-hover')); }
    nav.setAttribute('data-min', v ? '1' : '0');
  };
  let lastY = scrollY, acc = 0, raf = 0;
  const onScroll = () => {
    raf = 0;
    if (!mq.matches || document.body.classList.contains('search-open') || nav.classList.contains('is-press')) { lastY = scrollY; return; }
    const y = Math.max(0, scrollY), max = document.documentElement.scrollHeight - innerHeight;
    const dy = y - lastY; lastY = y;
    if (Math.abs(dy) < 1) return;
    acc = Math.sign(dy) === Math.sign(acc) ? acc + dy : dy;
    if (y < 80 || y > max - 8) setMin(false);
    else if (acc > 28) setMin(true);
    else if (acc < -12) setMin(false);
  };
  addEventListener('scroll', () => { if (!raf) raf = requestAnimationFrame(onScroll); }, { passive: true });
  nav.addEventListener('focusin', e => { if (nav.classList.contains('is-min') && e.target.matches(':focus-visible')) setMin(false); });

  // ---------- لمس و کشیدن ----------
  let down = false, moved = false, startX = 0, pid = null, captured = false, lastBuzz = -1;
  const idxAt = x => {
    let best = 0, bd = 1e9;
    items.forEach((li, i) => { const r = li.getBoundingClientRect(); if (!r.width) return; const d = Math.abs(x - (r.left + r.width / 2)); if (d < bd) { bd = d; best = i; } });
    return best;
  };
  const reset = () => {
    down = false; moved = false; captured = false; pid = null; lastBuzz = -1;
    nav.classList.remove('is-press'); items.forEach(li => li.classList.remove('is-hover'));
    hover = active; place(active, true);
  };
  ul.addEventListener('pointerdown', e => {
    if (nav.classList.contains('is-min') || (e.pointerType === 'mouse' && e.button !== 0)) return;
    down = true; moved = false; captured = false; pid = e.pointerId; startX = e.clientX;
    nav.classList.add('is-press');
    const i = idxAt(e.clientX); items.forEach((li, k) => li.classList.toggle('is-hover', k === i)); hover = -1; setHover(i, true);
  });
  ul.addEventListener('pointermove', e => {
    if (!down || e.pointerId !== pid) return;
    if (!moved && Math.abs(e.clientX - startX) > 6) {
      moved = true;
      try { ul.setPointerCapture(pid); captured = true; } catch (err) { }
    }
    const i = idxAt(e.clientX);
    if (i !== hover) { items.forEach((li, k) => li.classList.toggle('is-hover', k === i)); setHover(i, true); if (moved && i !== lastBuzz) { lastBuzz = i; buzz(6); } }
  });
  const finish = e => {
    if (!down || (e && e.pointerId !== pid)) return;
    const wasMoved = moved, i = hover, cap = captured;
    down = false; moved = false; captured = false;
    nav.classList.remove('is-press'); items.forEach(li => li.classList.remove('is-hover'));
    if (cap) { try { ul.releasePointerCapture(pid); } catch (err) { } }
    if (wasMoved) {
      const el = linkOf(items[i]);
      if (!el) { hover = active; place(active, true); return; }
      const same = el.tagName === 'A' && el.classList.contains('on');
      if (same) { hover = active; place(active, true); return; }
      selected = el.tagName === 'A' ? i : null; buzz(8);
      if (el.tagName === 'BUTTON') setTimeout(() => { hover = active; place(active, true); }, 380);
      el.click();
    } else {
      // لمس ساده: کلیکِ خودِ مرورگر کار را انجام می‌دهد؛ فقط عدسی روی تب لمس‌شده می‌ماند
      const el = linkOf(items[i]);
      if (el && el.tagName === 'BUTTON') setTimeout(() => { hover = active; place(active, true); }, 380);
    }
  };
  ul.addEventListener('pointerup', finish);
  ul.addEventListener('pointercancel', e => { if (down && e.pointerId === pid) reset(); });
  ul.addEventListener('lostpointercapture', e => { if (e.target === ul && down && e.pointerId === pid && moved) reset(); });
  ul.addEventListener('contextmenu', e => { if (down) e.preventDefault(); });
  ul.addEventListener('dragstart', e => e.preventDefault());

  // لمس نوار جمع‌شده آن را باز می‌کند (به‌جای رفتن به صفحه)
  nav.addEventListener('click', e => {
    if (nav.classList.contains('is-min')) { e.preventDefault(); e.stopPropagation(); setMin(false); return; }
    const li = e.target.closest('li'); if (!li) return;
    const i = items.indexOf(li); if (i < 0) return;
    const el = linkOf(li);
    if (el && el.tagName === 'A' && !el.classList.contains('on')) { selected = i; buzz(8); if (hover !== i) { hover = -1; setHover(i, true); } }
  }, true);

  // دکمه‌ی «بیشتر» در پنل‌ها: برگه باز شد، عدسی برگردد
  document.addEventListener('click', e => { if (e.target.closest('#sheet-back')) { hover = active; place(active, true); } });
})();
