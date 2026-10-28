/* تبدیل تاریخ شمسی ⇄ میلادی (هم‌الگوریتم با App\Support\Jalali) */
window.Jalali = (function () {
  const months = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
  const div = (a, b) => Math.trunc(a / b);
  function toJ(gy, gm, gd) {
    const g = [0,31,59,90,120,151,181,212,243,273,304,334];
    const gy2 = gm > 2 ? gy + 1 : gy;
    let days = 355666 + 365 * gy + div(gy2 + 3, 4) - div(gy2 + 99, 100) + div(gy2 + 399, 400) + gd + g[gm - 1];
    let jy = -1595 + 33 * div(days, 12053); days %= 12053;
    jy += 4 * div(days, 1461); days %= 1461;
    if (days > 365) { jy += div(days - 1, 365); days = (days - 1) % 365; }
    const jm = days < 186 ? 1 + div(days, 31) : 7 + div(days - 186, 30);
    const jd = 1 + (days < 186 ? days % 31 : (days - 186) % 30);
    return [jy, jm, jd];
  }
  function toG(jy, jm, jd) {
    jy += 1595;
    let days = -355668 + 365 * jy + div(jy, 33) * 8 + div((jy % 33) + 3, 4) + jd + (jm < 7 ? (jm - 1) * 31 : (jm - 7) * 30 + 186);
    let gy = 400 * div(days, 146097); days %= 146097;
    if (days > 36524) { gy += 100 * div(--days, 36524); days %= 36524; if (days >= 365) days++; }
    gy += 4 * div(days, 1461); days %= 1461;
    if (days > 365) { gy += div(days - 1, 365); days = (days - 1) % 365; }
    let gd = days + 1;
    const sal = [0,31,((gy % 4 === 0 && gy % 100 !== 0) || gy % 400 === 0) ? 29 : 28,31,30,31,30,31,31,30,31,30,31];
    let gm; for (gm = 0; gm < 13 && gd > sal[gm]; gm++) gd -= sal[gm];
    return [gy, gm, gd];
  }
  const leap = y => [1,5,9,13,17,22,26,30].includes(y % 33);
  const len = (y, m) => m <= 6 ? 31 : (m < 12 ? 30 : (leap(y) ? 30 : 29));
  const pad = n => String(n).padStart(2, '0');
  const iso = (jy, jm, jd) => { const [y, m, d] = toG(jy, jm, jd); return y + '-' + pad(m) + '-' + pad(d); };
  const fa = v => String(v).replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
  const todayJ = () => { const n = new Date(); return toJ(n.getFullYear(), n.getMonth() + 1, n.getDate()); };
  const todayISO = () => { const n = new Date(); return n.getFullYear() + '-' + pad(n.getMonth() + 1) + '-' + pad(n.getDate()); };
  const dowSat0 = (jy, jm, jd) => { const [y, m, d] = toG(jy, jm, jd); return (new Date(y, m - 1, d).getDay() + 1) % 7; };
  const fmt = (jy, jm, jd) => jy + '/' + pad(jm) + '/' + pad(jd);
  const parseISO = s => { const [y, m, d] = s.split('-').map(Number); return toJ(y, m, d); };
  return { months, toJ, toG, len, iso, fa, todayJ, todayISO, dowSat0, fmt, pad, parseISO };
})();
