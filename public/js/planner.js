/*
 * The planner window (opens when you click the laptop).
 * It shows the 4 time blocks of today, your tasks, and a form to add a task.
 * Every picture has a word next to it. The words are in js/text.js.
 */
PQ.planner = (function () {
  const h = PQ.h, icon = PQ.icon, T = () => PQ.T;
  let root = null, picking = null, formOpen = false, draft = null;

  const S = () => PQ.room.state;

  function open() {
    close();
    root = h('div', { class: 'panel planner', id: 'planner' });
    document.body.append(root);
    render();
  }

  function close() {
    if (root) { root.remove(); root = null; }
    picking = null; formOpen = false;
  }

  function newDraft() {
    const d = new Date(S().user.game_date + 'T00:00:00');
    d.setDate(d.getDate() + 2);
    return { title: '', type: 'practice', size: 'small', difficulty: 1, due_date: d.toISOString().slice(0, 10), cause_id: null };
  }

  function render() {
    if (!root) return;
    const st = S(), P = T().planner;
    root.replaceChildren(...[
      h('div', { class: 'bar' },
        PQ.btn('ui/icon_back.png', P.back, () => PQ.room.leavePlanner()),
        h('span', { class: 'ptitle' }, P.title),
        h('span', { class: 'stat-pair' },
          h('span', { class: 'date', title: P.today }, icon('ui/icon_calendar.png', 'ic-s'), h('span', { class: 'lbl' }, P.today), h('b', {}, st.plan.date.slice(5))),
          h('span', { class: 'pile', title: PQ.T.hudTip.backlog }, icon('ui/icon_pile.png', 'ic-s'), h('span', { class: 'lbl' }, P.backlog), h('b', {}, st.stats ? st.stats.backlog_penalty : 0)))
      ),
      PQ.say('hint', P.hint),
      h('div', { class: 'blocks' }, st.plan.blocks.map(blockCard)),
      picking ? picker() : null,
      h('div', { class: 'inbox' },
        h('div', { class: 'inbox-head' },
          h('span', { class: 'sect' }, P.tasks),
          PQ.btn('ui/icon_new.png', P.newTask, () => { formOpen = !formOpen; if (formOpen) draft = newDraft(); render(); }, 'new')),
        st.tasks.length ? st.tasks.map((t) => chip(t)) : PQ.say('hint', P.noTasks)
      ),
      formOpen ? form() : null
    ].filter(Boolean));          // replaceChildren would print the word "null" for a missing part
  }

  /** A task as a small card: cause icon, title, then size, difficulty, due date and status in words. */
  function chip(t, onclick) {
    const P = T().planner;
    const cause = S().causes.find((c) => c.id === t.cause_id) || { icon: 'cause_c1.png', name: '' };
    const sizeN = { small: 1, medium: 2, large: 3 }[t.size];
    const status = T().status[t.status];
    return h('div', { class: 'chip st-' + t.status, onclick, title: cause.name },
      h('div', { class: 'chip-top' },
        icon('causes/' + cause.icon, 'cause', cause.name),
        icon('tasktypes/type_' + t.type + '.png', 'ttype', T().taskType[t.type]),
        h('span', { class: 'ctitle' }, t.title),
        t.status === 'done' ? icon('ui/icon_check.png', 'mark') : null,
        t.status === 'pending' || t.status === 'overdue' ? icon('ui/icon_warn.png', 'mark') : null),
      h('div', { class: 'chip-meta' },
        h('span', { class: 'meta' }, T().taskType[t.type]),
        h('span', { class: 'meta' }, PQ.pips(sizeN), ' ', T().size[t.size]),
        h('span', { class: 'meta' }, PQ.stars(t.difficulty), ' ', T().diff[t.difficulty]),
        h('span', { class: 'meta due' }, P.due + ' ' + t.due_date.slice(5)),
        status ? h('span', { class: 'meta tag' }, status) : null)
    );
  }

  function blockCard(b) {
    const st = S(), P = T().planner;
    const task = st.tasks.find((t) => t.id === b.task_id);
    const card = h('div', { class: 'block' + (b.used ? ' used' : '') + ' ' + b.kind },
      h('div', { class: 'block-head' },
        h('span', { class: 'sect' }, P.block + ' ' + b.index),
        h('button', { class: 'kind', type: 'button', disabled: b.used, title: P.kindTip, 'aria-label': P.kindTip,
          onclick: () => setBlock(b.index, b.kind === 'task' ? 'free' : 'task', null) },
        icon(b.kind === 'task' ? 'ui/icon_task.png' : 'ui/icon_free.png', 'ic-s'),
        h('span', { class: 'lbl' }, b.kind === 'task' ? P.kindTask : P.kindFree)))
    );
    if (b.kind === 'task') {
      if (task) {
        card.append(chip(task));
        card.append(b.used
          ? h('div', { class: 'usedmark' }, icon('ui/icon_check.png', 'ic-s'), h('span', { class: 'lbl' }, P.used))
          : PQ.btn('ui/icon_play.png', P.start, () => PQ.session.start(b.index), 'play'));
      } else if (!b.used) {
        card.append(PQ.btn('ui/icon_new.png', P.pick, () => { picking = b.index; render(); }, 'add'));
      }
    }
    return card;
  }

  /** List of tasks that can still go into the chosen block. */
  function picker() {
    const st = S(), P = T().planner;
    const inBlock = st.plan.blocks.map((b) => b.task_id).filter(Boolean);
    const free = st.tasks.filter((t) => ['open', 'pending', 'overdue'].includes(t.status) && !inBlock.includes(t.id));
    return h('div', { class: 'picker' },
      h('div', { class: 'picker-head' },
        h('span', { class: 'sect' }, P.pickTitle),
        PQ.btn('ui/icon_back.png', P.cancel, () => { picking = null; render(); })),
      free.length ? free.map((t) => chip(t, () => setBlock(picking, 'task', t.id))) : PQ.say('hint', P.noFree));
  }

  async function setBlock(index, kind, taskId) {
    const r = await PQ.api('/planner/blocks', 'POST', { block_index: index, kind, task_id: taskId });
    if (!r.ok) PQ.fail(r);
    picking = null;
    await PQ.room.refresh();
    render();
  }

  /** The new task form. Every choice has a picture and a word. */
  function form() {
    const st = S(), F = T().form;
    const choice = (cls, selected, onclick, ...kids) => h('button', { type: 'button', class: 'opt ' + cls + (selected ? ' on' : ''), onclick }, ...kids);
    const set = (k, v) => () => { draft[k] = v; render(); };
    const picked = st.causes.find((c) => c.id === draft.cause_id);

    return h('div', { class: 'form' },
      h('div', { class: 'sect' }, F.heading),
      h('input', { type: 'text', class: 'title-in', maxlength: 120, 'aria-label': F.title, placeholder: F.title, value: draft.title,
        oninput: (e) => { draft.title = e.target.value; } }),
      h('div', { class: 'frow' }, h('span', { class: 'flabel' }, F.type),
        h('div', { class: 'row' }, ['practice', 'deep', 'memorize'].map((t) =>
          choice('', draft.type === t, set('type', t), icon('tasktypes/type_' + t + '.png', 'ic-s'), h('span', { class: 'lbl' }, T().taskType[t]))))),
      h('div', { class: 'frow' }, h('span', { class: 'flabel' }, F.size),
        h('div', { class: 'row' }, ['small', 'medium', 'large'].map((s, i) =>
          choice('', draft.size === s, set('size', s), PQ.pips(i + 1), h('span', { class: 'lbl' }, T().size[s]))))),
      h('div', { class: 'frow' }, h('span', { class: 'flabel' }, F.difficulty),
        h('div', { class: 'row' }, [1, 2, 3].map((n) =>
          choice('', draft.difficulty === n, set('difficulty', n), PQ.stars(n), h('span', { class: 'lbl' }, T().diff[n]))))),
      h('div', { class: 'frow' }, h('span', { class: 'flabel' }, F.due),
        h('input', { type: 'date', class: 'date-in', 'aria-label': F.due, value: draft.due_date, oninput: (e) => { draft.due_date = e.target.value; } })),
      h('div', { class: 'flabel' }, F.cause),
      h('div', { class: 'row causes' }, st.causes.map((c) =>
        choice('cause', draft.cause_id === c.id, set('cause_id', c.id), icon('causes/' + c.icon, 'cic', c.name), h('span', { class: 'cname' }, c.name)))),
      picked && picked.description ? PQ.say('hint cdesc', picked.description + '.') : null,
      h('div', { class: 'form-actions' },
        PQ.btn('ui/icon_back.png', F.cancel, () => { formOpen = false; render(); }),
        PQ.btn('ui/icon_check.png', F.save, saveTask, 'ok'))
    );
  }

  async function saveTask() {
    const F = T().form;
    if (!draft.title.trim()) { PQ.toast('ui/icon_warn.png', F.needTitle); return; }
    if (!draft.cause_id) { PQ.toast('ui/icon_warn.png', F.needCause); return; }
    if (!draft.due_date) { PQ.toast('ui/icon_warn.png', F.needDate); return; }
    const r = await PQ.api('/tasks', 'POST', draft);
    if (!r.ok) { PQ.fail(r); return; }
    formOpen = false;
    await PQ.room.refresh();
    render();
    PQ.toast('ui/icon_check.png', F.saved);
  }

  return { open, close, render };
})();
