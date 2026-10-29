(function () {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s), $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const reduce = matchMedia('(prefers-reduced-motion:reduce)').matches;

  /* ---------- ورود متحرک ---------- */
  if (window.gsap && !reduce) {
    const tl = gsap.timeline({ defaults: { ease: 'power3.out' } });
    tl.from('#auth-card', { y: 60, opacity: 0, scale: .96, duration: .9 })
      .from('.auth-logo img', { scale: 0, rotate: -40, duration: .8, ease: 'back.out(2)' }, '-=.55')
      .from('[data-a]', { y: 22, opacity: 0, duration: .55, stagger: .07 }, '-=.5')
      .from('.auth-top > *', { y: -16, opacity: 0, stagger: .1, duration: .5 }, 0.1)
      .from('.chip', { scale: .4, opacity: 0, stagger: .15, duration: .8, ease: 'back.out(1.8)' }, .5)
      .from('.pin-float', { y: -120, opacity: 0, duration: 1.1, ease: 'bounce.out' }, .35)
      .from('.scene .layer', { y: 80, opacity: 0, stagger: .08, duration: 1 }, 0);
  }

  /* ---------- پارالاکس با ماوس و ژیروسکوپ ---------- */
  const scene = $('#auth-scene');
  if (scene && !reduce) {
    const layers = $$('.layer', scene).map(l => ({ el: l, d: +l.dataset.depth || 10 }));
    const move = (x, y) => layers.forEach(({ el, d }) => { el.style.translate = `${x * d}px ${y * d * .5}px`; });
    scene.addEventListener('mousemove', e => { const r = scene.getBoundingClientRect(); move(-((e.clientX - r.left) / r.width - .5) * .8, -((e.clientY - r.top) / r.height - .5) * .8); });
    scene.addEventListener('mouseleave', () => move(0, 0));
    window.addEventListener('deviceorientation', e => { if (e.gamma == null) return; move(-Math.max(-30, Math.min(30, e.gamma)) / 40, -Math.max(-30, Math.min(30, (e.beta || 0) - 45)) / 60); });
  }

  /* ---------- متن چرخان ---------- */
  const rot = $$('#rot p'), dots = $$('#rot-dots i');
  if (rot.length) { let i = 0; setInterval(() => { rot[i].classList.remove('on'); dots[i].classList.remove('on'); i = (i + 1) % rot.length; rot[i].classList.add('on'); dots[i].classList.add('on'); }, 4200); }

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
