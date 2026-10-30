/* می‌جا — صفحه‌ی دانلود و به‌روزرسانی اپلیکیشن: تشخیص دستگاه، QR، راهنمای نصب، پل بومی */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var ua = navigator.userAgent || '';
  var root = $('#ap') || $('#up');
  if (!root) return;

  // ---- نوع دستگاه (کلاس روی ریشه؛ CSS بخش‌های مناسب را نشان می‌دهد)
  var native = window.MijaNative || null;
  var inApp = /MijaApp\//.test(ua) || !!native;
  var android = /Android/i.test(ua);
  var ios = /iPhone|iPad|iPod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  root.classList.add(inApp ? 'dev-inapp' : android ? 'dev-android' : ios ? 'dev-ios' : 'dev-desktop');

  var fa = function (n) { return String(n).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); };

  // ---- QR (فقط دسکتاپ)
  var qr = $('#qr');
  if (qr && window.qrcode) {
    try {
      var q = qrcode(0, 'M'); q.addData(root.getAttribute('data-url') || location.href); q.make();
      qr.innerHTML = q.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
    } catch (e) {}
  }

  // ---- کپی SHA
  $$('[data-copy]').forEach(function (b) {
    b.addEventListener('click', function () {
      var t = $(b.getAttribute('data-copy')); if (!t) return;
      var txt = t.textContent.trim();
      var done = function () { var o = b.textContent; b.textContent = 'کپی شد ✓'; setTimeout(function () { b.textContent = o; }, 1600); };
      if (navigator.clipboard) navigator.clipboard.writeText(txt).then(done, done); else done();
    });
  });

  // ---- راهنمای بعد از شروع دانلود
  var sheet = $('#ap-sheet'), bk = $('#ap-sheet-bk');
  function openSheet() {
    if (!sheet) return;
    sheet.hidden = false; bk.hidden = false;
    requestAnimationFrame(function () { sheet.classList.add('open'); bk.classList.add('open'); });
    var items = $$('.ap-mini li', sheet), i = 0;
    items.forEach(function (li) { li.classList.remove('on', 'done'); });
    items[0].classList.add('on');
    var bar = $('.ap-prog i', sheet); bar.style.transition = 'none'; bar.style.width = '0'; void bar.offsetWidth; bar.style.transition = 'width 3.2s ease'; bar.style.width = '100%';
    clearInterval(openSheet.t);
    openSheet.t = setInterval(function () {
      if (i < items.length - 1) { items[i].classList.remove('on'); items[i].classList.add('done'); i++; items[i].classList.add('on'); } else clearInterval(openSheet.t);
    }, 2400);
  }
  function closeSheet() {
    if (!sheet) return; clearInterval(openSheet.t);
    sheet.classList.remove('open'); bk.classList.remove('open');
    setTimeout(function () { sheet.hidden = true; bk.hidden = true; }, 260);
  }
  $$('[data-dl]').forEach(function (a) { a.addEventListener('click', function () { setTimeout(openSheet, 350); }); });
  $('#ap-sheet-x') && $('#ap-sheet-x').addEventListener('click', closeSheet);
  bk && bk.addEventListener('click', closeSheet);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeSheet(); });

  // ---- دکمه‌های داخل اپ
  $('#chk') && $('#chk').addEventListener('click', function () { if (native && native.checkUpdate) native.checkUpdate(); else location.href = '/app/update'; });

  // ---- صفحه‌ی به‌روزرسانی
  var up = $('#up');
  if (up) {
    var latest = parseInt(up.getAttribute('data-latest') || '0', 10);
    var latestName = up.getAttribute('data-latest-name') || '';
    var force = up.getAttribute('data-force') === '1';
    var cur = parseInt(up.getAttribute('data-cur') || '0', 10);
    var curName = '', known = cur > 0;
    var flavor = up.getAttribute('data-src') || '';
    var installer = '';
    if (native && native.info) {
      try { var inf = JSON.parse(native.info()); cur = inf.versionCode || cur; known = true; curName = inf.versionName || ''; flavor = inf.flavor || flavor; installer = inf.installer || ''; } catch (e) {}
    } else {
      var m = ua.match(/MijaApp\/([\d.]+) \(code (\d+); flavor (\w+)\)/);
      if (m) { known = true; curName = m[1]; cur = parseInt(m[2], 10); flavor = m[3]; }
    }
    var store = flavor === 'store' || /bazaar|myket|market/i.test(flavor) || installer === 'com.farsitel.bazaar' || installer === 'ir.mservices.market';
    var state = !latest ? 'none' : (!known ? 'unknown' : (cur >= latest ? 'uptodate' : (force ? 'force' : 'available')));
    up.setAttribute('data-state', state);
    up.setAttribute('data-mode', store ? 'store' : 'direct');
    if (curName || cur) { $$('[data-cur-name]').forEach(function (e) { e.textContent = curName ? fa(curName) : fa(cur); }); }
    var go = $('#up-go');
    if (go) {
      go.addEventListener('click', function (e) {
        if (native && native.startUpdate) { e.preventDefault(); native.startUpdate(); return; }
        if (!go.hasAttribute('data-dl')) return;
        setTimeout(openSheet, 350);
      });
    }
  }
})();
