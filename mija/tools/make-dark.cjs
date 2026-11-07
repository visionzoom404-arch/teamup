/**
 * تولید خودکار حالت تیره: همه‌ی استایل‌های روشن (lux/glass/resp/more/appdl) را می‌خواند
 * و نسخه‌ی تیره‌ی رنگ‌ها را زیر html[data-theme="dark"] می‌نویسد (public/css/dark.auto.css).
 * اجرا (فقط وقتی CSS اصلی عوض شد):  cd tools && npm i postcss@8 && node make-dark.cjs
 * بعد از آن، رفع‌های دستی در public/css/dark.css اعمال می‌شود.
 */
const fs = require('fs'), path = require('path');
const postcss = require('postcss');
const css = path.join(__dirname, '..', 'public', 'css');
const FILES = ['lux.css', 'glass.css', 'resp.css', 'more.css', 'appdl.css'];
const P = 'html[data-theme="dark"]';

/* ---------- رنگ ---------- */
const NAMED = { white: [255, 255, 255, 1], black: [0, 0, 0, 1] };
function parse(tok) {
  tok = tok.trim().toLowerCase();
  if (NAMED[tok]) return NAMED[tok].slice();
  let m = tok.match(/^#([0-9a-f]{3,8})$/);
  if (m) { let h = m[1]; if (h.length === 3 || h.length === 4) h = h.split('').map(c => c + c).join(''); if (h.length !== 6 && h.length !== 8) return null; return [parseInt(h.slice(0, 2), 16), parseInt(h.slice(2, 4), 16), parseInt(h.slice(4, 6), 16), h.length === 8 ? parseInt(h.slice(6), 16) / 255 : 1]; }
  m = tok.match(/^rgba?\(([^)]+)\)$/);
  if (m) { const p = m[1].split(/[\s,\/]+/).filter(Boolean).map(x => x.endsWith('%') ? parseFloat(x) * 2.55 : parseFloat(x)); if (p.length < 3 || p.some(isNaN)) return null; return [p[0], p[1], p[2], p[3] === undefined ? 1 : (m[1].includes('%') && p[3] > 1 ? p[3] / 2.55 : p[3])]; }
  return null;
}
function rgb2hsl(r, g, b) { r /= 255; g /= 255; b /= 255; const mx = Math.max(r, g, b), mn = Math.min(r, g, b); let h = 0, s = 0; const l = (mx + mn) / 2; if (mx !== mn) { const d = mx - mn; s = l > .5 ? d / (2 - mx - mn) : d / (mx + mn); h = mx === r ? (g - b) / d + (g < b ? 6 : 0) : mx === g ? (b - r) / d + 2 : (r - g) / d + 4; h /= 6; } return [h, s, l]; }
function hsl2rgb(h, s, l) { const f = (p, q, t) => { if (t < 0) t += 1; if (t > 1) t -= 1; if (t < 1 / 6) return p + (q - p) * 6 * t; if (t < 1 / 2) return q; if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6; return p; }; if (!s) { const v = Math.round(l * 255); return [v, v, v]; } const q = l < .5 ? l * (1 + s) : l + s - l * s, p = 2 * l - q; return [f(p, q, h + 1 / 3), f(p, q, h), f(p, q, h - 1 / 3)].map(v => Math.round(v * 255)); }
const out = (r, g, b, a) => a >= 0.995 ? `rgb(${r},${g},${b})` : `rgba(${r},${g},${b},${+a.toFixed(3)})`;
const isWhiteish = (c) => { const [, s, l] = rgb2hsl(c[0], c[1], c[2]); return l > 0.97 && s < 0.4 || (c[0] > 245 && c[1] > 240 && c[2] > 225); };

function mapBg(c) {
  const [r, g, b, a] = c; const [h, s, l] = rgb2hsl(r, g, b);
  if (a < 0.02) return null;
  if (isWhiteish(c) && a < 0.3) return null;                     // هایلایت‌های کم‌رنگ روی عکس/رنگ؛ همان بماند
  if (l >= 0.78) { const nl = 0.115 + (1 - l) * 0.45; const [R, G, B] = hsl2rgb(s < 0.05 ? 0.08 : h, Math.max(Math.min(s, 0.32) * (l > 0.97 ? 0.5 : 1), 0.13), nl); return out(R, G, B, Math.min(1, a * (a < 1 ? 1.12 : 1))); }
  return null;
}
function mapFg(c) {
  const [r, g, b, a] = c; const [h, s, l] = rgb2hsl(r, g, b);
  if (l < 0.55) { const nl = Math.min(0.95, Math.max(0.6, 1 - l * 0.78)); const [R, G, B] = hsl2rgb(h, Math.min(s, 0.85) * 0.9, nl); return out(R, G, B, a); }
  return null;
}
function mapLine(c) {
  const [r, g, b, a] = c; const [, s, l] = rgb2hsl(r, g, b);
  if (a < 0.02) return null;
  if (l >= 0.78 && s < 0.9) return out(255, 255, 255, a >= 0.999 ? 0.12 : Math.min(0.16, a * 0.15 + 0.03));
  if (l >= 0.78) return out(255, 255, 255, 0.12);
  if (l <= 0.25 && a > 0.4) return out(255, 255, 255, 0.28);
  return null;
}
function mapShadow(c) {
  const [r, g, b, a] = c;
  if (isWhiteish(c)) return out(255, 255, 255, Math.min(0.07, a * 0.08));
  const [, s, l] = rgb2hsl(r, g, b);
  if (l > 0.8 && s < 0.5) return out(255, 255, 255, Math.min(0.08, a * 0.08));
  return out(0, 0, 0, Math.min(0.62, a * 2.2 + 0.08));
}
const COLOR_RE = /#[0-9a-fA-F]{3,8}\b|rgba?\([^)]*\)|\bwhite\b|\bblack\b/g;
function transformValue(val, fn) {
  // رنگ‌های داخل url(...) دست نخورند
  const parts = val.split(/(url\((?:[^()]|\([^)]*\))*\))/);
  let changed = false;
  const res = parts.map(p => {
    if (p.startsWith('url(')) return p;
    return p.replace(COLOR_RE, tok => { const c = parse(tok); if (!c) return tok; const n = fn(c); if (n) { changed = true; return n; } return tok; });
  }).join('');
  return changed ? res : null;
}
const PROPS = {
  bg: /^(background|background-color|background-image)$/,
  fg: /^(color|fill|stroke|caret-color|text-decoration-color|-webkit-text-fill-color)$/,
  line: /^(border|border-top|border-bottom|border-left|border-right|border-inline-start|border-inline-end|border-block|border-color|border-top-color|border-bottom-color|border-left-color|border-right-color|outline|outline-color|column-rule)$/,
  shadow: /^(box-shadow)$/,
};
// انتخابگرهایی که نباید خودکار تبدیل شوند (در dark.css دستی هستند یا نیازی ندارند)
// متن روی دکمه‌ها/آیتم‌های فعالِ طلایی همان تیره بماند
const SKIP_FG = [/^\.btn(?!-light|-ghost|-blue|-dark)[\w-]*(:hover|:active|:focus)?$/, /\.ap-copy-btn/];
const SKIP_SEL = [/^:root/, /^html$/, /^body::before/, /\.btn(::|:)/, /^\.btn(\b|$).*::(before|after)/, /::selection/, /\.vc\b/, /\.vc-/, /\.leaflet/, /\.swiper-pagination/];

function prefixSel(sel) {
  sel = sel.trim();
  if (sel === 'html' || sel === ':root') return null;
  if (/^html\b/.test(sel)) return sel.replace(/^html/, 'html[data-theme="dark"]');
  return `${P} ${sel}`;
}
function processRule(rule, container) {
  const decls = [];
  const hasSatBg = rule.nodes.some(d => {
    if (d.type !== 'decl' || !/^background/.test(d.prop)) return false;
    if (/gold-g|var\(--brand\)|var\(--ok\)|var\(--green\)|var\(--sale\)|var\(--orange\)/.test(d.value)) return true;
    const cs = (d.value.match(COLOR_RE) || []).map(parse).filter(Boolean);
    return cs.some(c => { const [, sa, l] = rgb2hsl(c[0], c[1], c[2]); return c[3] > 0.5 && sa >= 0.45 && l >= 0.3 && l <= 0.78; });
  });
  rule.nodes.forEach(d => {
    if (d.type !== 'decl') return;
    let fn = null;
    for (const k of Object.keys(PROPS)) if (PROPS[k].test(d.prop)) fn = { bg: mapBg, fg: mapFg, line: mapLine, shadow: mapShadow }[k];
    if (!fn) return;
    if (fn === mapFg && (hasSatBg || rule.selectors.some(sl => SKIP_FG.some(re => re.test(sl.trim()))))) return;                         // متن تیره روی پس‌زمینه‌ی نارنجی/طلایی همان بماند
    if (/var\(--gold-g\)|gold-g/.test(rule.toString()) && fn === mapFg) return;
    const nv = transformValue(d.value, fn);
    if (nv) decls.push(`${d.prop}:${nv.replace(/\s*!important\s*$/i, '')}!important`);
  });
  if (!decls.length) return null;
  const sels = rule.selectors.filter(s => !SKIP_SEL.some(re => re.test(s.trim()))).map(prefixSel).filter(Boolean);
  if (!sels.length) return null;
  return `${sels.join(',')}{${decls.join(';')}}`;
}
function walk(nodes, wrappers, outArr) {
  nodes.forEach(n => {
    if (n.type === 'rule') { if (n.parent && n.parent.type === 'atrule' && /keyframes/i.test(n.parent.name)) return; const r = processRule(n); if (r) outArr.push(wrappers.length ? wrappers.reduceRight((acc, w) => `${w}{${acc}}`, r) : r); }
    else if (n.type === 'atrule' && /^(media|supports)$/i.test(n.name)) walk(n.nodes || [], wrappers.concat(`@${n.name} ${n.params}`), outArr);
  });
}
let result = '/* تولید خودکار توسط tools/make-dark.js — دستی ویرایش نشود؛ رفع‌های دستی در dark.css */\n';
FILES.forEach(f => { const src = fs.readFileSync(path.join(css, f), 'utf8'); const root = postcss.parse(src); const arr = []; walk(root.nodes, [], arr); result += `\n/* ${f} */\n` + arr.join('\n') + '\n'; });
fs.writeFileSync(path.join(css, 'dark.auto.css'), result);
console.log('dark.auto.css', (result.length / 1024).toFixed(1) + 'KB');
