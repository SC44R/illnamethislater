const fs = require('fs');
const { JSDOM } = require('jsdom');
const ROOT = require('path').join(__dirname, '..');

let html = fs.readFileSync(ROOT + '/resources/views/room.blade.php', 'utf8');
html = html.replace(/\{\{\s*asset\('([^']+)'\)\s*\}\}/g, '/$1').replace('{{ csrf_token() }}', 'tok')
  .replace(/<script[\s\S]*?<\/script>/g, '');
const dom = new JSDOM(html, { url: 'http://localhost/', runScripts: 'outside-only', pretendToBeVisual: true });
const w = dom.window;
for (const el of ['view']) {
  Object.defineProperty(w.document.getElementById(el), 'clientWidth', { value: 1000 });
  Object.defineProperty(w.document.getElementById(el), 'clientHeight', { value: 600 });
}
const errors = [];
w.addEventListener('error', (e) => errors.push('window error: ' + e.message));
process.on('unhandledRejection', (e) => errors.push('unhandled rejection: ' + (e && e.stack || e)));

/* ---------- fake server (only to exercise the browser code) ---------- */
const MODE = process.argv[2] || 'play';        // play | flow | slip
const srv = { auth: false, typeId: null, tasks: [], blocks: [1, 2, 3, 4].map((i) => ({ index: i, kind: i % 2 ? 'task' : 'free', task_id: null, used: false })), session: null, xp: 0, log: [] };
const types = [1, 2, 3, 4].map((i) => ({ id: i, code: ['avoider', 'perfectionist', 'crammer', 'daydreamer'][i - 1], name: 'T' + i, icon: 'type_x.png', base_focus: 80, base_willpower: 5, base_calm: 5, base_drive: 2, description: '' }));
const causes = Array.from({ length: 11 }, (_, i) => ({ id: i + 1, code: 'C' + (i + 1), name: 'c' + i, icon: 'cause_c' + (i + 1) + '.png' }));
const stateJson = () => srv.auth ? {
  auth: true, user: { name: 'T', level: 1, xp: srv.xp, coins: 0, type_id: srv.typeId, game_date: '2026-01-10' },
  stats: srv.typeId ? { focus_max: 80, willpower: 5, calm: 5, drive: 2, backlog_penalty: 0 } : null,
  types, causes, tasks: srv.tasks, plan: { date: '2026-01-10', blocks: srv.blocks }, active_session_id: null,
} : { auth: false };
const ok = (data) => ({ ok: true, status: 200, json: async () => data });
const bad = (status, data) => ({ ok: false, status, json: async () => data });
w.fetch = async (url, opt = {}) => {
  const body = opt.body ? JSON.parse(opt.body) : null;
  srv.log.push((opt.method || 'GET') + ' ' + url);
  if (url === '/state') return ok(stateJson());
  if (url === '/login') { srv.auth = true; return ok({ ok: true }); }
  if (url === '/character') { srv.typeId = body.type_id; return ok({ ok: true }); }
  if (url === '/tasks') { srv.tasks.unshift({ id: 1, title: body.title, type: body.type, size: body.size, difficulty: body.difficulty, due_date: body.due_date, cause_id: body.cause_id, status: 'open', minigame: 'beat_click' }); return ok({ ok: true }); }
  if (url === '/planner/blocks') { const b = srv.blocks.find((x) => x.index === body.block_index); b.kind = body.kind; b.task_id = body.task_id; return ok({ ok: true }); }
  if (url === '/planner/end-day') return ok({ ok: true, new_pending: 1, new_overdue: 0, game_date: '2026-01-11' });
  if (/^\/blocks\/\d+\/start$/.test(url)) { srv.session = { points: 0, target: 60, focus: 70, events: [] }; return ok({ session_id: 7, preview: { modifiers: [], total_modifier: 0, odds: { slip: 5, hard: 30, normal: 35, easy: 25, flow: 5 } } }); }
  if (url === '/sessions/7/roll') {
    if (MODE === 'flow') return ok({ roll: { d20: 20, total: 20, band: 'flow', modifiers: [], notes: [] }, outcome: { type: 'flow', scene: 'task_cancelled', summary: { results: { state: 'flow', band: 'flow', stars: 0, xp: 10, coins: 5 } } } });
    if (MODE === 'slip') return ok({ roll: { d20: 1, total: 1, band: 'slip', modifiers: [], notes: [] }, outcome: { type: 'slip', scene: 'slip_generic', summary: { results: { state: 'slip', band: 'slip', stars: 0, xp: 5, coins: 0 } } } });
    const prompts = ['tap', 'hold', 'swipe', 'triple_tap'];
    return ok({ roll: { d20: 12, total: 12, band: 'normal', modifiers: [], notes: [] }, outcome: { type: 'play', config: {
      sessionId: 7, minigame: 'beat_click', seed: 1, band: 'normal', difficulty: 3, rounds: 6, target_points: 60,
      params: { zoneWidth: 0.95, perfectWidth: 0.6, speed: 1.2, shift: true, drift: 0.04, fakeZones: 1 },
      layout: Array.from({ length: 9 }, () => ({ center: 0.5, fake: 0.1 })), focus_max: 80, focus_current: 70,
      distractions: prompts.map((p, i) => ({ id: 100 + i, at_progress: 0.2 + i * 0.2, tag: ['PHONE', 'SCREEN', 'URGE', 'SPACE'][i], tier: 1 + (i === 3 ? 2 : 0), name: 'D' + i,
        icon: 'creature_phone_buzz.png', prompt: p, prompt_count: i === 3 ? 2 : 1, window_ms: 3000, damage: 4, effect: ['PHONE', 'SCREEN', 'URGE', 'SPACE'][i] })) } } });
  }
  if (url === '/sessions/7/round') {
    if (body.result === 'MISS') srv.session.target += 10; else srv.session.points += body.result === 'PERFECT' ? 10 : 6;
    const s = srv.session, done = s.points >= s.target;
    const out = { state: done ? 'cleared' : 'playing', finished: done, focus_current: s.focus, focus_max: 80, points: s.points, target: s.target, combo: 0, extra_rounds: 0 };
    if (done) out.results = { state: 'cleared', band: 'normal', stars: 3, xp: 20, coins: 10, focus_left: s.focus, focus_max: 80 };
    return ok(out);
  }
  if (url === '/sessions/7/dismiss') {
    const failed = body.event_id === 101;      // fail the second one on purpose to exercise effects
    srv.session.focus -= failed ? 4 : 0;
    return ok({ result: failed ? 'failed' : 'dismissed', damage: failed ? 4 : 0, effect: failed ? 'SCREEN' : null,
      session: { state: 'playing', finished: false, focus_current: srv.session.focus, focus_max: 80, points: srv.session.points, target: srv.session.target, combo: 0 } });
  }
  if (url === '/sessions/7/abandon') return ok({ ok: true });
  return bad(404, { error: 'unknown ' + url });
};

/* ---------- load the real scripts ---------- */
w.eval('window.PQ = window.PQ || {}; PQ.assets = "/assets"; PQ.initialState = ' + JSON.stringify({ auth: false }) + ';');
for (const f of ['text', 'util', 'beatclick', 'effects', 'planner', 'session', 'room']) w.eval(fs.readFileSync(`${ROOT}/public/js/${f}.js`, 'utf8'));
const PQ = w.PQ, doc = w.document;
const $ = (s) => doc.querySelector(s), $$ = (s) => [...doc.querySelectorAll(s)];
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const fire = (el, type, init = {}) => el.dispatchEvent(new w.MouseEvent(type, { bubbles: true, cancelable: true, ...init }));
const must = (cond, msg) => { if (!cond) { console.log('FAIL: ' + msg); process.exitCode = 1; } else console.log('ok:   ' + msg); };
const waitFor = async (fn, ms = 20000) => { const t = Date.now(); while (Date.now() - t < ms) { if (fn()) return true; await sleep(100); } return false; };

(async () => {
  PQ.room.boot();
  must($('#login') && $('#login').style.display !== 'none', 'login form shows on the laptop screen');

  // bad submit stays on the login (shake)
  $('#login').elements.email.value = 'a@b.c'; $('#login').elements.password.value = 'secret';
  fire($('#toggle-register'), 'click'); must($('#login').classList.contains('register'), 'register toggle shows the name row');
  fire($('#toggle-register'), 'click');
  $('#login').dispatchEvent(new w.Event('submit', { bubbles: true, cancelable: true }));
  must(await waitFor(() => $('#charselect'), 12000), 'after login the character choice opens');
  must($$('.tcard').length === 4, 'four procrastinator types are shown');

  fire($$('.tcard')[0], 'click');
  must(await waitFor(() => $('#spots').classList.contains('on') && !$('#charselect'), 8000), 'after choosing a type the room hotspots appear');
  must($$('.spot').length === 2 && $('#hud').children.length === 9, 'two hotspots and the stats display are there');

  fire($$('.spot')[0], 'click');
  must(await waitFor(() => $('#planner'), 4000), 'laptop hotspot opens the planner');
  must($$('.block').length === 4, 'planner shows 4 blocks');

  fire($('.btn.new'), 'click');
  must($('.form'), 'new task form opens');
  const title = $('.title-in'); title.value = 'Math set'; title.dispatchEvent(new w.Event('input', { bubbles: true }));
  fire($$('.opt.cause')[5], 'click');
  fire($('.btn.ok'), 'click');
  must(await waitFor(() => $$('.inbox .chip').length === 1, 3000), 'saved task shows in the inbox');

  fire($('.block .btn.add'), 'click');
  must(await waitFor(() => $('.picker'), 2000), 'empty task block opens the task picker');
  fire($('.picker .chip'), 'click');
  must(await waitFor(() => $('.block .btn.play'), 3000), 'task placed in the block, play button appears');
  fire($('.block .btn.play'), 'click');
  must(await waitFor(() => $('#roll'), 4000), 'starting a block opens the dice scene');
  must($$('.oddsbar .seg').length === 5, 'odds bar has 5 bands');

  fire($('.dieb'), 'click');
  if (MODE === 'play') {
    must(await waitFor(() => $('#game'), 9000), 'roll leads to the minigame');
    const seen = new Set(); let hits = 0, finished = false;
    const t0 = Date.now();
    while (Date.now() - t0 < 60000 && !$('#results')) {
      const popup = $('#popup');
      if (popup && !popup.dataset.handled) {
        popup.dataset.handled = '1';
        const kind = [...popup.querySelectorAll('img.prompt')][0].src.match(/prompt_(\w+)\.png/)[1];
        seen.add(kind);
        const n = popup.querySelectorAll('img.prompt').length;
        (async () => {
          if (kind === 'tap') fire(popup, 'pointerdown');
          if (kind === 'triple_tap') for (let i = 0; i < 3 * n; i++) { fire(popup, 'pointerdown'); await sleep(50); }
          if (kind === 'hold') { fire(popup, 'pointerdown'); await sleep(1000); fire(popup, 'pointerup'); }
          if (kind === 'swipe') { fire(popup, 'pointerdown', { clientX: 0 }); fire(popup, 'pointermove', { clientX: 120 }); }
        })();
      }
      if (!popup) { doc.dispatchEvent(new w.KeyboardEvent('keydown', { code: 'Space', bubbles: true })); hits++; }
      await sleep(220);
    }
    must(!!$('#results'), 'minigame ends in the results panel (after ' + hits + ' hits)');
    must(seen.has('tap') && seen.has('hold') && seen.has('swipe'), 'tap, hold and swipe prompts all appeared: ' + [...seen].join(','));
    must($$('#results .star').length === 3, 'results show 3 stars');
  } else {
    must(await waitFor(() => $('#results'), 9000), MODE + ' roll leads straight to the results scene');
    must($('#results img.scene'), 'a scene picture is shown');
  }
  fire($('#results .btn.ok'), 'click');
  must(await waitFor(() => !$('#results') && $('#spots').classList.contains('on'), 5000), 'results close and the room comes back');

  fire($$('.spot')[1], 'click');
  must(await waitFor(() => $('.night'), 4000), 'bed hotspot starts the end-of-day fade');
  await sleep(4000);
  must(!$('.night'), 'night fade ends');
  must(srv.log.includes('POST /planner/end-day'), 'end-day request was sent');

  console.log(errors.length ? 'ERRORS:\n' + errors.join('\n') : 'no script errors');
  if (errors.length) process.exitCode = 1;
  process.exit();
})();
