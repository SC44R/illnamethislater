/* Small helpers shared by all scripts. Everything lives in the global object PQ. */
window.PQ = window.PQ || {};

PQ.A = (path) => PQ.assets + '/' + path;                       // path of a picture
PQ.$ = (selector, root = document) => root.querySelector(selector);
PQ.sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

/** h('div', {class: 'x', onclick: fn}, child, child) builds a DOM element. */
PQ.h = function (tag, attrs, ...kids) {
  const el = document.createElement(tag);
  for (const [key, value] of Object.entries(attrs || {})) {
    if (value === false || value === null || value === undefined) continue;
    if (key === 'class') el.className = value;
    else if (key === 'style') el.style.cssText = value;
    else if (key.startsWith('on')) el.addEventListener(key.slice(2), value);
    else if (value === true) el.setAttribute(key, '');
    else el.setAttribute(key, value);
  }
  for (const kid of kids.flat()) {
    if (kid === null || kid === undefined || kid === false) continue;
    el.append(kid.nodeType ? kid : document.createTextNode(String(kid)));
  }
  return el;
};

/** A picture from the assets folder. */
PQ.icon = (path, cls = '', alt = '') => PQ.h('img', { src: PQ.A(path), class: ('ic ' + cls).trim(), alt, draggable: 'false' });

/** Calls the server. Returns { ok, status, data }. */
PQ.api = async function (url, method = 'GET', body) {
  const options = {
    method,
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
    },
  };
  if (body !== undefined) {
    options.headers['Content-Type'] = 'application/json';
    options.body = JSON.stringify(body);
  }
  let res;
  try { res = await fetch(url, options); } catch (e) { return { ok: false, status: 0, data: null }; }   // offline
  let data = null;
  try { data = await res.json(); } catch (e) { /* no JSON body */ }
  return { ok: res.ok, status: res.status, data };
};

/** A short floating message: a picture and some words (or a number). */
PQ.toast = function (iconPath, text, ms = 2600) {
  // Toasts live in one container, so several at once stack up instead of covering each other.
  let box = document.getElementById('toasts');
  if (!box) { box = PQ.h('div', { id: 'toasts', class: 'toasts' }); document.body.append(box); }
  const t = PQ.h('div', { class: 'toast' }, iconPath ? PQ.icon(iconPath, 'ic-s') : null, text !== undefined ? PQ.h('b', { class: 'toast-text' }, text) : null);
  box.append(t);
  setTimeout(() => t.remove(), ms);
};

/** Shows why a server call failed, in words. r = { ok, status, data } from PQ.api. */
PQ.fail = (r) => PQ.toast('ui/icon_warn.png', PQ.errorText(r));

/** A button with a picture AND a word: PQ.btn('ui/icon_back.png', 'BACK', onclick, 'extra-class'). */
PQ.btn = (iconPath, label, onclick, cls = '') => PQ.h('button', { type: 'button', class: ('btn ' + cls).trim(), 'aria-label': label, onclick },
  iconPath ? PQ.icon(iconPath, 'ic-s') : null, PQ.h('span', { class: 'lbl' }, label));

/** A small caption: PQ.say('hint', 'Some words'). */
PQ.say = (cls, text) => PQ.h('div', { class: cls }, text);

PQ.pips = (filled, total = 3) => PQ.h('span', { class: 'pips' },
  Array.from({ length: total }, (_, i) => PQ.icon(i < filled ? 'ui/pip_full.png' : 'ui/pip_empty.png', 'pip')));

PQ.stars = (filled, total = 3) => PQ.h('span', { class: 'stars' },
  Array.from({ length: total }, (_, i) => PQ.icon(i < filled ? 'ui/star_full.png' : 'ui/star_empty.png', 'star')));
