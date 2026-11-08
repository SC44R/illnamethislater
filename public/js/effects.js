/*
 * Lingering effects: what a FAILED distraction does to the minigame (Section 3.4.0 of the design document).
 * The effect code is the distraction tag. Sprint 1 has four effects: PHONE, SCREEN, URGE, SPACE.
 * Add a new effect by adding one function here.
 */
PQ.effects = {
  /** root = the game panel element, game = the BeatClick object */
  apply(code, root, game) {
    const key = this[code] ? code : 'PHONE';
    // Say in words what just happened to the player (the text lives in text.js).
    PQ.toast('ui/icon_warn.png', PQ.T.effect[key] || PQ.T.effect.PHONE, 3200);
    this[key].call(this, root, game);
  },

  shake(root) {
    root.classList.add('shake');
    setTimeout(() => root.classList.remove('shake'), 600);
  },

  // Phone: screen shake, the green zone is 30% smaller for 3 rounds
  PHONE(root, game) {
    this.shake(root);
    game.addEffect({ type: 'shrink', rounds: 3 });
  },

  // Social would be similar (chat bubbles). It is not built yet, so it uses the phone effect.
  SOCIAL(root, game) { this.PHONE(root, game); },

  // Screen: the play area dims and a video overlay must be tapped 3 times to close
  SCREEN(root, game) {
    const area = PQ.$('.bc-area', root);
    if (!area || PQ.$('.dim-overlay', area)) return;
    let clicks = 0;
    const overlay = PQ.h('div', { class: 'dim-overlay' }, PQ.icon('distractions/prompt_tap.png', 'big'), PQ.h('b', {}, '3'), PQ.h('div', { class: 'dim-text' }, PQ.T.tapToClose));
    overlay.addEventListener('pointerdown', (e) => {
      e.stopPropagation();
      clicks++;
      overlay.children[1].textContent = String(3 - clicks);
      if (clicks >= 3) overlay.remove();
    });
    area.append(overlay);
  },

  // Urge ("later"): grey tint, the next round needs a Perfect (a Good counts as a Miss)
  URGE(root, game) {
    game.addEffect({ type: 'needPerfect', rounds: 1 });
    root.classList.add('grey-tint');
    setTimeout(() => root.classList.remove('grey-tint'), 4000);
  },

  // Space (noise and clutter): clutter sprites cover the edges of the green zone for 8 seconds
  SPACE(root, game) {
    const track = PQ.$('.bc-track', root);
    if (!track) return;
    const clutter = PQ.h('div', { class: 'clutter' });
    for (let i = 0; i < 6; i++) {
      clutter.append(PQ.h('i', { style: `left:${8 + Math.random() * 84}%;top:${Math.random() * 70}%;transform:rotate(${Math.random() * 60 - 30}deg)` }));
    }
    track.append(clutter);
    setTimeout(() => clutter.remove(), 8000);
  },
};
