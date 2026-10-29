(function () {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s), $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const reduce = matchMedia('(prefers-reduced-motion:reduce)').matches;

  /* ---------- ورود آرام ---------- */
  if (window.gsap && !reduce) {
    gsap.from('#auth-card .auth-logo', { y: 14, opacity: 0, duration: .7, ease: 'power3.out' });
    gsap.from('[data-a]', { y: 16, opacity: 0, duration: .6, stagger: .06, ease: 'power3.out', delay: .12 });
  }

  /* ---------- نمایش رمز ---------- */
  $$('[data-eye]').forEach(b => b.addEventListener('click', () => {
    const i = b.parentElement.querySelector('input'); const show = i.type === 'password'; i.type = show ? 'text' : 'password'; b.style.color = show ? 'var(--red)' : '';
    window.gsap && gsap.fromTo(b, { scale: .7 }, { scale: 1, duration: .4, ease: 'back.out(3)' });
  }));

  /* ---------- شماره موبایل: ارقام فارسی → لاتین ---------- */
  $$('[data-phone]').forEach(i => i.addEventListener('input', () => {
    const v = i.value.replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/\D/g, '').slice(0, 11);
    if (v !== i.value) i.value = v;
  }));

  /* ---------- قدرت رمز ---------- */
  const st = $('#strength'), pw = $('[data-strength]');
  if (st && pw) pw.addEventListener('input', () => {
    const v = pw.value; let s = 0;
    if (v.length >= 6) s++; if (v.length >= 9) s++; if (/[A-Za-z]/.test(v) && /\d/.test(v)) s++; if (/[^A-Za-z0-9]/.test(v) || (/[A-Z]/.test(v) && /[a-z]/.test(v))) s++;
    if (!v) s = 0; st.dataset.s = s; $('span', st).textContent = ['', 'ضعیف', 'متوسط', 'خوب', 'عالی'][s];
  });

  /* ---------- نقش مهمان/میزبان ---------- */
  const seg = $('#role-seg');
  if (seg) {
    const apply = (r, animate) => {
      seg.dataset.r = r;
      $$('#perks li').forEach(li => {
        const on = li.dataset.for === r; if (on) li.hidden = false;
        if (window.gsap && animate) { on ? gsap.fromTo(li, { x: 20, opacity: 0 }, { x: 0, opacity: 1, duration: .45, stagger: .08 }) : (li.hidden = true); } else li.hidden = !on;
      });
    };
    $$('input[name=role]', seg).forEach(i => i.addEventListener('change', () => apply(i.value, true)));
    apply(seg.dataset.r, false);
  }

  /* ---------- ارسال فرم: اعتبارسنجی سبک، لرزش، لودینگ ---------- */
  const form = $('#auth-form');
  if (form) form.addEventListener('submit', e => {
    let bad = null;
    $$('.fl input[required]', form).forEach(i => {
      const fl = i.parentElement; fl.classList.remove('has-err');
      const invalid = !i.value || (i.dataset.phone !== undefined && !/^09\d{9}$/.test(i.value)) || (i.name === 'password' && i.minLength > 0 && i.value.length < i.minLength);
      if (invalid) { fl.classList.add('has-err'); bad = bad || fl; }
    });
    const pc = $('[name=password_confirmation]', form);
    if (pc && pc.value !== $('[name=password]', form).value) { pc.parentElement.classList.add('has-err'); bad = bad || pc.parentElement; }
    if (bad) { e.preventDefault(); void bad.offsetWidth; window.gsap && gsap.fromTo('#auth-card', { x: 0 }, { x: 0, keyframes: { x: [0, -10, 10, -7, 7, 0] }, duration: .45 }); bad.querySelector('input').focus(); return; }
    const b = $('.btn-auth', form); b.classList.add('loading'); setTimeout(() => b.setAttribute('disabled', ''), 0);
  });
  $$('.fl input').forEach(i => i.addEventListener('input', () => i.parentElement.classList.remove('has-err')));
  if ($('.fl.has-err') && window.gsap) gsap.fromTo('#auth-card', { x: 0 }, { keyframes: { x: [0, -10, 10, -7, 7, 0] }, duration: .5, delay: .9 });
})();
