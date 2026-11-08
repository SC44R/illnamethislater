/*
 * The pixel room: the camera, the login on the laptop screen, character choice, hotspots and the stats display.
 * This is only the View. The server decides every rule.
 */
PQ.room = (function () {
  const h = PQ.h, icon = PQ.icon;
  const W = 1024, H = 682;                                   // size of the room picture
  const SCREEN = { x: 291, y: 365, w: 114 };            // the laptop screen inside the picture

  const view = PQ.$('#view'), cam = PQ.$('#cam'), spots = PQ.$('#spots'), hud = PQ.$('#hud');
  const R = { state: PQ.initialState, mode: 'login' };
  let cur = { x: SCREEN.x, y: SCREEN.y, z: 1 };

  /* ------------------------------------------------------------------ camera */
  const fit = () => Math.max(view.clientWidth / W, view.clientHeight / H);   // 'cover': the picture fills the whole screen
  const loginZoom = () => Math.min(0.62 * view.clientWidth / (SCREEN.w * fit()), 9);

  function go(x, y, zoom, seconds, instant) {
    const S = fit() * zoom, vw = view.clientWidth, vh = view.clientHeight;
    cam.style.transitionDuration = instant ? '0s' : (seconds || 2.2) + 's';
    // Keep the picture covering the screen: never slide it far enough to show empty edges.
    const tx = Math.min(0, Math.max(vw - W * S, vw / 2 - x * S)), ty = Math.min(0, Math.max(vh - H * S, vh / 2 - y * S));
    cam.style.transform = `translate(${tx}px,${ty}px) scale(${S})`;
    cam.style.setProperty('--inv', String(1 / S));           // lets labels in the room keep a readable size on screen
    cur = { x, y, z: zoom };
  }
  R.go = go;
  function layout() { R.mode === 'login' ? go(SCREEN.x, SCREEN.y, loginZoom(), 0, true) : go(cur.x, cur.y, cur.z, 0, true); }
  addEventListener('resize', layout);

  /* ------------------------------------------------------------------ state and stats display */
  R.refresh = async function () {
    const r = await PQ.api('/state');
    if (r.ok) R.state = r.data;
    updateHud();
    return R.state;
  };

  function updateHud() {
    const s = R.state, T = PQ.T;
    if (!s.auth || !s.stats) { hud.replaceChildren(); return; }
    const item = (path, key, value) => h('span', { class: 'hi', title: T.hudTip[key] || '' }, icon(path, 'ic-s'), h('span', { class: 'lbl' }, T.hud[key]), h('b', {}, value));
    hud.replaceChildren(
      h('span', { class: 'hi who' }, h('b', {}, String(s.user.name || '').slice(0, 12).toUpperCase()), h('span', { class: 'lbl' }, T.hud.level + ' ' + s.user.level)),
      item('stats/stat_focus.png', 'focus', s.stats.focus_max), item('stats/stat_willpower.png', 'willpower', s.stats.willpower),
      item('stats/stat_calm.png', 'calm', s.stats.calm), item('stats/stat_drive.png', 'drive', s.stats.drive),
      item('ui/icon_pile.png', 'backlog', s.stats.backlog_penalty), item('ui/icon_xp.png', 'xp', s.user.xp),
      item('ui/icon_coin.png', 'coins', s.user.coins), item('ui/icon_calendar.png', 'date', s.user.game_date.slice(5)));
  }

  /* ------------------------------------------------------------------ hotspots */
  const SPOTS = [
    { key: 'laptop', x: 291, y: 318, icon: 'ui/hotspot_laptop.png', label: PQ.T.spot.planner, action: openPlanner },
    { key: 'bed', x: 700, y: 440, icon: 'ui/hotspot_bed.png', label: PQ.T.spot.bed, action: endDay },
  ];
  SPOTS.forEach((s) => {
    spots.append(h('button', { class: 'spot', style: `left:${s.x}px;top:${s.y}px`, 'aria-label': s.label, onclick: s.action }, icon(s.icon), h('span', { class: 'spot-label' }, s.label)));
  });

  R.hideRoomUI = function () { spots.classList.remove('on'); hud.classList.remove('on'); };

  R.showRoom = function () {
    R.mode = 'room';
    go(W / 2, H / 2, 1, 1.2);
    setTimeout(() => { if (R.mode === 'room') { spots.classList.add('on'); hud.classList.add('on'); } }, 1200);
    updateHud();
  };

  function openPlanner() {
    R.hideRoomUI(); R.mode = 'zoom';
    go(291, 365, 3.0, 1.1);
    setTimeout(() => PQ.planner.open(), 950);
  }
  R.leavePlanner = function () { PQ.planner.close(); R.showRoom(); };

  async function endDay() {
    R.hideRoomUI(); R.mode = 'zoom';
    go(700, 440, 2.2, 1.0);
    await PQ.sleep(900);
    const night = h('div', { class: 'night' }, icon('ui/icon_sleep.png', 'big'), h('div', { class: 'night-text' }, PQ.T.sleeping));
    document.body.append(night);
    requestAnimationFrame(() => night.classList.add('on'));
    const r = await PQ.api('/planner/end-day', 'POST');
    await PQ.sleep(1100);
    await R.refresh();
    night.classList.remove('on');
    await PQ.sleep(600);
    night.remove();
    if (r.ok) {
      const E = PQ.T.endDay;
      PQ.toast('ui/icon_calendar.png', E.newDay(R.state.user.game_date.slice(5)));
      if (r.data.new_pending) PQ.toast('ui/icon_pile.png', E.pending(r.data.new_pending));
      if (r.data.new_overdue) PQ.toast('ui/icon_warn.png', E.overdue(r.data.new_overdue));
    } else PQ.fail(r);
    R.showRoom();
  }

  /* ------------------------------------------------------------------ character choice (first time only) */
  function characterSelect() {
    const T = PQ.T;
    const stat = (path, key, value, max) => h('div', { class: 'srow' }, icon(path, 'ic-s'), h('span', { class: 'lbl' }, T.stats[key]),
      PQ.pips(Math.max(1, Math.round(value / max * 5)), 5), h('b', {}, value));
    const root = h('div', { class: 'panel charselect', id: 'charselect' },
      h('div', { class: 'cs-head' }, h('div', { class: 'cs-title' }, T.chooseType), h('div', { class: 'hint' }, T.chooseHint)),
      R.state.types.map((t) => h('button', { class: 'tcard', 'aria-label': t.name, title: t.description || t.name, onclick: () => pick(t.id) },
        icon('types/avatar_' + t.code + '_idle.png', 'avatar'),
        h('div', { class: 'tname' }, icon('types/' + t.icon, 'ticon'), h('span', {}, t.name.toUpperCase())),
        h('div', { class: 'tdesc' }, t.description || ''),
        stat('stats/stat_focus.png', 'focus', t.base_focus, 100), stat('stats/stat_willpower.png', 'willpower', t.base_willpower, 10),
        stat('stats/stat_calm.png', 'calm', t.base_calm, 10), stat('stats/stat_drive.png', 'drive', t.base_drive, 10))));
    document.body.append(root);

    async function pick(id) {
      const r = await PQ.api('/character', 'POST', { type_id: id });
      if (!r.ok) { PQ.fail(r); return; }
      root.remove();
      await R.refresh();
      R.showRoom();
    }
  }

  async function afterLogin() {
    await R.refresh();
    if (!R.state.user.type_id) { R.mode = 'room'; go(W / 2, H / 2, 1, 1.2); characterSelect(); return; }
    // A session left open (for example the page was closed in the middle of a task) is closed first.
    if (R.state.active_session_id) {
      await PQ.api('/sessions/' + R.state.active_session_id + '/abandon', 'POST');
      await R.refresh();
      PQ.toast('ui/icon_warn.png', PQ.T.recovered);
    }
    R.showRoom();
  }

  /* ------------------------------------------------------------------ login on the laptop screen */
  const form = PQ.$('#login'), desk = PQ.$('#desk'), boot = PQ.$('#boot'), msg = PQ.$('#msg');
  const L = PQ.T.login;
  PQ.$('#t-title1').textContent = PQ.T.title1;
  PQ.$('#t-title2').textContent = PQ.T.title2;
  form.elements.name.placeholder = L.name;
  form.elements.email.placeholder = L.email;
  form.elements.password.placeholder = L.password;

  const say = (text, good) => { msg.textContent = text || ''; msg.classList.toggle('good', !!good); };
  function labels() {
    const reg = form.classList.contains('register');
    PQ.$('#toggle-register .lbl').textContent = reg ? L.toLogin : L.toRegister;
    PQ.$('#go .lbl').textContent = reg ? L.signUp : L.logIn;
    PQ.$('#go').setAttribute('aria-label', reg ? L.signUp : L.logIn);
    PQ.$('#toggle-register img').src = PQ.A(reg ? 'ui/icon_back.png' : 'ui/icon_new.png');
    PQ.$('#toggle-register').setAttribute('aria-label', reg ? L.toLogin : L.toRegister);
  }
  labels();
  PQ.$('#toggle-register').addEventListener('click', () => { form.classList.toggle('register'); say(''); labels(); });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const register = form.classList.contains('register');
    const body = { email: form.elements.email.value, password: form.elements.password.value };
    if (register) body.name = form.elements.name.value;

    const r = await PQ.api(register ? '/register' : '/login', 'POST', body);
    if (!r.ok) {
      form.classList.remove('shake'); void form.offsetWidth; form.classList.add('shake');   // a shake = something is wrong
      say(PQ.errorText(r));                                                                 // ...and the words say what
      return;
    }
    say(L.welcome, true);
    // Logging in gives the session a new security (CSRF) token. The page must use the new one from now on.
    if (r.data && r.data.csrf) document.querySelector('meta[name=csrf-token]').content = r.data.csrf;
    boot.style.display = 'block';
    requestAnimationFrame(() => { boot.firstElementChild.style.width = '100%'; });
    await PQ.sleep(1000);
    form.style.display = 'none';
    desk.style.display = 'block';
    await PQ.sleep(500);
    R.mode = 'room';
    go(W / 2, H / 2, 1, 2.6);
    await PQ.sleep(2700);
    afterLogin();
  });

  /* ------------------------------------------------------------------ start */
  R.boot = async function () {
    layout();
    if (R.state.auth) {
      form.style.display = 'none';
      desk.style.display = 'block';
      R.mode = 'room';
      go(W / 2, H / 2, 1, 0, true);
      afterLogin();
    }
  };

  return R;
})();
