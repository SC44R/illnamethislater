/*
 * Beat Click minigame (View only: it draws and reads input).
 * The server decides rewards and Focus. This class only judges PERFECT / GOOD / MISS and reports it.
 *
 * Marker position is a triangle wave of the "active time" (time while the game is not paused):
 *   0 -> 1 -> 0 -> 1 ...
 */
PQ.BeatClick = class {
  constructor(root, cfg, callbacks) {
    this.cfg = cfg;
    this.cb = callbacks;          // { onRound(result), onFrame() }
    this.t = 0;                   // active time in seconds
    this.round = 0;               // index into cfg.layout
    this.running = false;
    this.locked = false;          // spam guard
    this.effects = { shrinkRounds: 0, needPerfectRounds: 0 };
    this.root = root;
    this.build();
    this.onKey = (e) => { if (e.code === 'Space' && !e.repeat) { e.preventDefault(); this.hit(); } };
    this.onPointer = (e) => { if (e.target.closest('.bc-track')) this.hit(); };
    document.addEventListener('keydown', this.onKey);
    this.root.addEventListener('pointerdown', this.onPointer);
  }

  build() {
    const h = PQ.h;
    this.zone = h('div', { class: 'bc-zone' }, this.perfect = h('div', { class: 'bc-perfect' }));
    this.fake = h('div', { class: 'bc-fake' });
    this.marker = h('div', { class: 'bc-marker' });
    this.label = h('div', { class: 'bc-label' });
    this.track = h('div', { class: 'bc-track' }, this.fake, this.zone, this.marker);
    this.root.replaceChildren(this.label, this.track);
    this.layoutZone();
  }

  layoutEntry() { return this.cfg.layout[Math.min(this.round, this.cfg.layout.length - 1)]; }

  zoneWidth() { return this.cfg.params.zoneWidth * (this.effects.shrinkRounds > 0 ? 0.7 : 1); }
  perfectWidth() { return this.cfg.params.perfectWidth * (this.effects.shrinkRounds > 0 ? 0.7 : 1); }

  zoneCenter() {
    const drift = this.cfg.params.drift || 0;
    return this.layoutEntry().center + drift * Math.sin(this.t * 0.8);
  }

  markerPos() {
    const p = (this.t * this.cfg.params.speed) % 2;
    return p < 1 ? p : 2 - p;
  }

  layoutZone() {
    const c = this.zoneCenter(), w = this.zoneWidth();
    this.zone.style.left = ((c - w / 2) * 100) + '%';
    this.zone.style.width = (w * 100) + '%';
    this.perfect.style.width = (this.perfectWidth() / w * 100) + '%';
    const f = this.layoutEntry().fake;
    this.fake.style.display = f === null ? 'none' : 'block';
    if (f !== null) {
      this.fake.style.left = ((f - w / 2) * 100) + '%';
      this.fake.style.width = (w * 100) + '%';
    }
  }

  start() { this.running = true; this.last = performance.now(); requestAnimationFrame((n) => this.loop(n)); }
  pause() { this.running = false; }
  resume() { if (!this.running) { this.running = true; this.last = performance.now(); requestAnimationFrame((n) => this.loop(n)); } }
  destroy() {
    this.running = false;
    document.removeEventListener('keydown', this.onKey);
    this.root.removeEventListener('pointerdown', this.onPointer);
  }

  loop(now) {
    if (!this.running) return;
    const dt = Math.min(0.05, (now - this.last) / 1000);
    this.last = now;
    this.t += dt;
    this.marker.style.left = (this.markerPos() * 100) + '%';
    this.layoutZone();
    if (this.cb.onFrame) this.cb.onFrame();
    requestAnimationFrame((n) => this.loop(n));
  }

  /** Judge a click. */
  hit() {
    if (!this.running || this.locked) return;
    this.locked = true;
    setTimeout(() => { this.locked = false; }, 150);

    const d = Math.abs(this.markerPos() - this.zoneCenter());
    let result = 'MISS';
    if (d <= this.perfectWidth() / 2) result = 'PERFECT';
    else if (d <= this.zoneWidth() / 2) result = 'GOOD';

    // lingering effect "URGE": the next round needs a Perfect
    if (this.effects.needPerfectRounds > 0) {
      if (result === 'GOOD') result = 'MISS';
      this.effects.needPerfectRounds--;
    }
    if (this.effects.shrinkRounds > 0) this.effects.shrinkRounds--;

    this.showLabel(result);
    this.round++;
    this.layoutZone();
    this.cb.onRound(result);
  }

  showLabel(result) {
    const G = PQ.T.game;
    this.label.textContent = result === 'PERFECT' ? G.perfect : result === 'GOOD' ? G.good : G.miss;
    this.label.className = 'bc-label show ' + result.toLowerCase();
    clearTimeout(this._lt);
    this._lt = setTimeout(() => { this.label.className = 'bc-label'; }, 500);
  }

  /** Lingering effects from failed distractions. */
  addEffect(e) {
    if (e.type === 'shrink') this.effects.shrinkRounds = Math.max(this.effects.shrinkRounds, e.rounds);
    if (e.type === 'needPerfect') this.effects.needPerfectRounds = Math.max(this.effects.needPerfectRounds, e.rounds);
  }
};
