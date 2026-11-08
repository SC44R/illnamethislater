/*
 * One play of a task: the dice scene, the minigame with distraction pop-ups, and the results.
 * The browser only draws and reads input. The server rolls the dice and decides every result.
 */
PQ.session = (function () {
  const h = PQ.h, icon = PQ.icon, T = PQ.T;
  const BANDS = ['slip', 'hard', 'normal', 'easy', 'flow'];

  /* ------------------------------------------------------------------ start and the dice scene */
  async function start(blockIndex) {
    const r = await PQ.api('/blocks/' + blockIndex + '/start', 'POST');
    if (!r.ok) { PQ.fail(r); return; }
    PQ.planner.close();
    PQ.room.hideRoomUI();
    PQ.room.go(512, 286, 1, 0.8);
    rollScene(r.data.session_id, r.data.preview);
  }

  function rollScene(id, preview) {
    const D = T.dice, odds = preview.odds, total = preview.total_modifier;
    const bar = h('div', { class: 'oddsbar' }, BANDS.map((b) =>
      h('div', { class: 'seg ' + b, style: `flex:${Math.max(odds[b], 0.001)}`, title: T.band[b] + ' ' + odds[b] + '%' },
        odds[b] >= 12 ? icon('bands/band_' + b + '.png', 'band-s') : null, odds[b] > 0 ? h('b', {}, odds[b] + '%') : null)));
    const legend = h('div', { class: 'legend' }, BANDS.map((b) =>
      h('span', { class: 'leg ' + b }, h('i', {}), T.band[b] + (odds[b] > 0 ? ' ' + odds[b] + '%' : ' 0%'))));
    const mods = (preview.modifiers || []).filter((m) => m.value !== 0);

    const die = icon('dice/d20_tumble_1.png', 'die');
    const bandBox = h('div', { class: 'bandbox' });
    const hint = PQ.say('hint blink', D.tapToRoll);
    const root = h('div', { class: 'panel roll', id: 'roll' },
      h('div', { class: 'rollbar' },
        PQ.btn('ui/icon_back.png', D.back, async () => { await PQ.api('/sessions/' + id + '/abandon', 'POST'); await PQ.room.refresh(); root.remove(); PQ.room.showRoom(); }, 'quit'),
        h('span', { class: 'ptitle' }, D.title)),
      h('div', { class: 'mod' }, icon('dice/d20_tumble_1.png', 'ic-s'), h('span', { class: 'lbl' }, D.bonus), h('b', {}, (total >= 0 ? '+' : '') + total),
        mods.map((m) => h('span', { class: 'modchip' }, (T.mod[m.code] || m.code.toUpperCase()) + ' ' + (m.value > 0 ? '+' : '') + m.value))),
      PQ.say('sect', D.odds),
      bar, legend,
      h('div', { class: 'desk' }, icon('dice/dice_tray.png', 'tray'), h('button', { class: 'dieb', 'aria-label': D.tapToRoll, onclick: throwDie }, die)),
      hint,
      bandBox
    );
    document.body.append(root);

    let rolling = false;
    async function throwDie() {
      if (rolling) return;
      rolling = true;
      die.classList.add('tumble');
      hint.textContent = D.rolling; hint.classList.remove('blink');
      const request = PQ.api('/sessions/' + id + '/roll', 'POST');
      let frame = 0;
      const timer = setInterval(() => {
        frame++;
        die.src = frame % 2 ? PQ.A('dice/d20_tumble_' + (frame % 8 + 1) + '.png') : PQ.A('dice/d20_face_' + String(1 + Math.floor(Math.random() * 20)).padStart(2, '0') + '.png');
      }, 90);
      const [res] = await Promise.all([request, PQ.sleep(1400)]);
      clearInterval(timer);
      die.classList.remove('tumble');
      if (!res.ok) { PQ.fail(res); root.remove(); PQ.room.showRoom(); return; }

      const roll = res.data.roll;
      die.src = PQ.A('dice/d20_face_' + String(roll.d20).padStart(2, '0') + '.png');
      await PQ.sleep(700);
      hint.textContent = '';
      bandBox.append(icon('bands/band_' + roll.band + '.png', 'band-big pop'),
        h('div', { class: 'bandinfo pop' },
          h('div', { class: 'bandname ' + roll.band }, T.band[roll.band]),
          h('div', { class: 'rolltotal' }, D.roll + ' ' + roll.d20 + ' + ' + D.bonus + ' ' + (roll.total - roll.d20) + ' = ' + roll.total),
          h('div', { class: 'bandtext' }, T.bandText[roll.band])));
      await PQ.sleep(2600);                                   // time to read what the band means

      root.remove();
      const outcome = res.data.outcome;
      if (outcome.type === 'play') runGame(id, outcome.config, roll.band);
      else results(outcome.summary.results, outcome.scene);
    }
  }

  /* ------------------------------------------------------------------ the minigame */
  function runGame(id, cfg, band) {
    const G = T.game;
    const focusFill = h('div', { class: 'fill focus' }), focusNum = h('b', {});
    const progFill = h('div', { class: 'fill prog' }), progNum = h('b', {}, '0%'), comboNum = h('b', {}, '0');
    const area = h('div', { class: 'bc-area' }, h('div', { class: 'bc-root' }));
    const root = h('div', { class: 'panel game', id: 'game' },
      h('div', { class: 'hudrow' },
        PQ.btn('ui/icon_back.png', G.quit, quit, 'quit'),
        h('span', { class: 'bandtag ' + band }, icon('bands/band_' + band + '.png', 'band-s'), T.band[band]),
        h('span', { class: 'spacer' }),
        icon('ui/icon_combo.png', 'ic-s'), h('span', { class: 'lbl' }, G.combo), comboNum),
      h('div', { class: 'hudrow' }, icon('ui/icon_focus.png', 'ic-s'), h('span', { class: 'lbl w' }, G.focus), h('div', { class: 'bar wide' }, focusFill), focusNum),
      h('div', { class: 'hudrow' }, icon('ui/icon_task.png', 'ic-s'), h('span', { class: 'lbl w' }, G.progress), h('div', { class: 'bar wide' }, progFill), progNum),
      area,
      PQ.say('hint how', G.how)
    );
    document.body.append(root);

    let progress = 0, nextEvent = 0, popupOpen = false, ended = false, queue = Promise.resolve();
    const events = cfg.distractions.slice().sort((a, b) => a.at_progress - b.at_progress);

    function hud(s) {
      focusFill.style.width = Math.max(0, s.focus_current / s.focus_max * 100) + '%';
      focusFill.classList.toggle('low', s.focus_current / s.focus_max < 0.4);
      focusNum.textContent = s.focus_current;
      progress = Math.min(1, s.points / s.target);
      progFill.style.width = (progress * 100) + '%';
      progNum.textContent = Math.round(progress * 100) + '%';
      comboNum.textContent = s.combo;
    }
    hud({ focus_current: cfg.focus_current, focus_max: cfg.focus_max, points: 0, target: cfg.target_points, combo: 0 });

    const game = new PQ.BeatClick(PQ.$('.bc-root', area), cfg, {
      onFrame: () => {
        if (popupOpen || ended) return;
        const ev = events[nextEvent];
        if (ev && progress >= ev.at_progress) { nextEvent++; showPopup(ev); }
      },
      onRound: (result) => {
        // results are sent in order, one after the other
        queue = queue.then(async () => {
          if (ended) return;
          const r = await PQ.api('/sessions/' + id + '/round', 'POST', { result });
          if (!r.ok) return;
          hud(r.data);
          if (r.data.finished) finish(r.data);
        });
      },
    });
    game.start();

    function finish(summary) {
      ended = true;
      game.destroy();
      PQ.$('#popup') && PQ.$('#popup').remove();
      root.remove();
      results(summary.results, summary.state);
    }

    async function quit() {
      ended = true;
      game.destroy();
      PQ.$('#popup') && PQ.$('#popup').remove();
      root.remove();
      await PQ.api('/sessions/' + id + '/abandon', 'POST');
      await PQ.room.refresh();
      PQ.room.showRoom();
    }

    /* ---------------- distraction pop-up ---------------- */
    function showPopup(ev) {
      popupOpen = true;
      game.pause();
      const P = T.popup;
      const prompts = Array.from({ length: ev.prompt_count }, () => icon('distractions/prompt_' + ev.prompt + '.png', 'prompt'));
      const ring = h('div', { class: 'ring', style: `animation-duration:${ev.window_ms}ms` });
      const say = h('div', { class: 'ptext' }, P[ev.prompt] || P.tap);
      const steps = ev.prompt_count > 1 ? h('div', { class: 'psteps' }, P.step(0, ev.prompt_count)) : null;
      const popup = h('div', { id: 'popup', class: 'popup tier' + ev.tier },
        h('div', { class: 'card' }, ring, h('div', { class: 'alert' }, P.alert), h('div', { class: 'cname' }, ev.name),
          icon('distractions/' + ev.icon, 'creature', ev.name), h('div', { class: 'prompts' }, prompts), say, steps));
      popup._say = say; popup._steps = steps;
      root.append(popup);

      runPrompt(ev, popup, prompts, async (ok, elapsed) => {
        popup.classList.add(ok ? 'good' : 'bad');
        popup._say.textContent = ok ? T.popup.nice : T.popup.fail;
        const r = await PQ.api('/sessions/' + id + '/dismiss', 'POST', { event_id: ev.id, success: ok, elapsed_ms: Math.round(elapsed) });
        if (r.ok && r.data.damage) popup._say.textContent = T.popup.fail + ' ' + T.popup.damage(r.data.damage);
        await PQ.sleep(650);
        popup.remove();
        popupOpen = false;
        if (!r.ok) { game.resume(); return; }
        hud(r.data.session);
        if (r.data.result === 'failed' && !r.data.session.finished) PQ.effects.apply(r.data.effect, root, game);
        if (r.data.session.finished) { finish(r.data.session); return; }
        game.resume();
      });
    }
  }

  /**
   * Reads the reflex action: tap, hold (0.9 s), swipe (90 px) or triple-tap.
   * done(success, elapsedMs) is called once.
   */
  function runPrompt(ev, popup, promptIcons, done) {
    const began = performance.now();
    let steps = 0, finished = false, holdTimer = null, startX = 0, startY = 0, down = false, taps = 0;

    const timeout = setTimeout(() => end(false), ev.window_ms);
    function end(ok) {
      if (finished) return;
      finished = true;
      clearTimeout(timeout); clearTimeout(holdTimer);
      popup.removeEventListener('pointerdown', onDown);
      popup.removeEventListener('pointermove', onMove);
      popup.removeEventListener('pointerup', onUp);
      document.removeEventListener('keydown', onKeyDown);
      document.removeEventListener('keyup', onKeyUp);
      done(ok, performance.now() - began);
    }
    function step() {
      if (promptIcons[steps]) promptIcons[steps].classList.add('done');
      steps++;
      if (popup._steps) popup._steps.textContent = PQ.T.popup.step(Math.min(steps, ev.prompt_count), ev.prompt_count);
      taps = 0;
      if (steps >= ev.prompt_count) end(true);
    }
    function onDown(e) {
      down = true; startX = e.clientX; startY = e.clientY;
      if (ev.prompt === 'tap') step();
      else if (ev.prompt === 'triple_tap') { taps++; if (taps >= 3) step(); }
      else if (ev.prompt === 'hold') { clearTimeout(holdTimer); holdTimer = setTimeout(step, 900); popup.classList.add('holding'); }
    }
    function onMove(e) {
      if (down && ev.prompt === 'swipe' && Math.abs(e.clientX - startX) >= 90) { down = false; step(); }
    }
    function onUp() { down = false; clearTimeout(holdTimer); popup.classList.remove('holding'); }
    // Space bar works like a finger: tap, triple-tap, hold. For swipe the arrow keys work.
    function onKeyDown(e) {
      if (e.repeat) return;
      if (e.code === 'Space') { e.preventDefault(); onDown({ clientX: 0, clientY: 0 }); }
      if (ev.prompt === 'swipe' && (e.code === 'ArrowRight' || e.code === 'ArrowLeft')) step();
    }
    function onKeyUp(e) { if (e.code === 'Space') onUp(); }

    popup.addEventListener('pointerdown', onDown);
    popup.addEventListener('pointermove', onMove);
    popup.addEventListener('pointerup', onUp);
    document.addEventListener('keydown', onKeyDown);
    document.addEventListener('keyup', onKeyUp);
  }

  /* ------------------------------------------------------------------ results */
  function results(res, scene) {
    const R = T.result;
    const flowOrSlip = res.state === 'flow' || res.state === 'slip';
    // Flow and Slip have their own scene pictures. For cleared / failed the coloured title below is the banner
    // (the placeholder banner pictures had stars baked in, which disagreed with the real star count).
    const picture = flowOrSlip
      ? icon('scenes/scene_' + (res.state === 'flow' ? 'task_cancelled' : 'slip_generic') + '.png', 'scene')
      : null;
    const key = ['cleared', 'failed', 'flow', 'slip'].includes(res.state) ? res.state : 'failed';

    const root = h('div', { class: 'panel results', id: 'results' },
      h('div', { class: 'rtitle ' + key }, R[key]),
      picture,
      PQ.say('hint', R[key + 'Text']),
      res.state === 'cleared' ? h('div', { class: 'rstars' }, h('span', { class: 'lbl' }, R.stars), PQ.stars(res.stars)) : null,
      h('div', { class: 'nums' },
        h('span', { class: 'num' }, icon('ui/icon_xp.png', 'ic-s'), h('span', { class: 'lbl' }, R.xp), h('b', {}, '+' + res.xp)),
        h('span', { class: 'num' }, icon('ui/icon_coin.png', 'ic-s'), h('span', { class: 'lbl' }, R.coins), h('b', {}, '+' + res.coins)),
        res.band ? h('span', { class: 'num' }, icon('bands/band_' + res.band + '.png', 'band-s'), h('span', { class: 'lbl' }, T.band[res.band] || res.band)) : null),
      PQ.btn('ui/icon_check.png', R.cont, async () => { root.remove(); await PQ.room.refresh(); PQ.room.showRoom(); }, 'ok')
    );
    document.body.append(root);
  }

  return { start };
})();
