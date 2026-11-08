/*
 * Every word the player sees lives in this one file.
 * To change a label, fix a typo or translate the game, edit it here. No other file has display text.
 * Labels are UPPERCASE (short, pixel-font friendly). Sentences use normal case so they are easier to read.
 */
window.PQ = window.PQ || {};

PQ.T = {
  /* login on the laptop screen */
  title1: 'ILLNAME',
  title2: 'THISLATER',
  login: {
    name: 'Your name', email: 'Email', password: 'Password (6+ letters)',
    logIn: 'LOG IN', signUp: 'SIGN UP',
    toRegister: 'NEW PLAYER?', toLogin: 'BACK TO LOG IN',
    welcome: 'WELCOME!',
  },

  /* the room */
  spot: { planner: 'PLANNER', bed: 'END DAY' },
  sleeping: 'SLEEPING...',
  hud: { focus: 'FOCUS', willpower: 'WILL', calm: 'CALM', drive: 'DRIVE', backlog: 'BACKLOG', xp: 'XP', coins: 'COINS', date: 'DATE', level: 'LV' },
  hudTip: {
    focus: 'Focus: your health bar during a task. At 0 you lose the task.',
    willpower: 'Willpower', calm: 'Calm', drive: 'Drive: adds to your dice roll.',
    backlog: 'Backlog: unfinished tasks. Makes dice rolls worse.', xp: 'Experience points', coins: 'Coins', date: 'Game date',
  },
  endDay: { pending: (n) => n + (n === 1 ? ' TASK' : ' TASKS') + ' MOVED TO BACKLOG', overdue: (n) => n + ' OVERDUE', newDay: (d) => 'NEW DAY ' + d },
  recovered: 'UNFINISHED TASK CLOSED',

  /* character choice */
  chooseType: 'CHOOSE YOUR TYPE',
  chooseHint: 'Pick the kind of procrastinator you are. It sets your starting stats.',
  stats: { focus: 'FOCUS', willpower: 'WILL', calm: 'CALM', drive: 'DRIVE' },

  /* planner */
  planner: {
    title: 'PLANNER', back: 'BACK', today: 'TODAY', backlog: 'BACKLOG',
    hint: 'Put a task in a block, then press START.',
    block: 'BLOCK', kindTask: 'TASK', kindFree: 'FREE', kindTip: 'Press to switch between TASK and FREE time',
    pick: 'PICK TASK', start: 'START', used: 'DONE', pickTitle: 'PICK A TASK FOR THIS BLOCK', cancel: 'CANCEL',
    noFree: 'No free tasks. Add one below.',
    tasks: 'ALL TASKS', noTasks: 'No tasks yet. Press NEW TASK.', newTask: 'NEW TASK',
    due: 'DUE', size: 'SIZE', difficulty: 'DIFFICULTY',
  },
  form: {
    heading: 'NEW TASK', title: 'What do you need to do?', type: 'TYPE', size: 'SIZE', difficulty: 'HOW HARD', due: 'DUE DATE',
    cause: 'WHY DO YOU PUT IT OFF?', save: 'SAVE TASK', cancel: 'CANCEL',
    needTitle: 'ADD A TITLE', needCause: 'PICK A REASON', needDate: 'PICK A DUE DATE', saved: 'TASK SAVED',
  },
  taskType: { practice: 'PRACTICE', deep: 'DEEP WORK', memorize: 'MEMORIZE' },
  size: { small: 'SMALL', medium: 'MEDIUM', large: 'LARGE' },
  diff: { 1: 'EASY', 2: 'MEDIUM', 3: 'HARD' },
  status: { open: '', pending: 'IN BACKLOG', overdue: 'OVERDUE', done: 'DONE', cancelled: 'CANCELLED' },

  /* dice scene */
  dice: {
    title: 'FATE ROLL', tapToRoll: 'TAP THE DIE TO ROLL', bonus: 'BONUS', roll: 'ROLL', total: 'TOTAL', odds: 'YOUR ODDS', back: 'BACK', rolling: 'ROLLING...',
  },
  mod: { drive: 'DRIVE', cards: 'CARDS', gear: 'GEAR', buff: 'BUFF', pity: 'LUCKY BREAK', backlog: 'BACKLOG', iou: 'IOU' },
  band: { slip: 'SLIP', hard: 'HARD', normal: 'NORMAL', easy: 'EASY', flow: 'FLOW' },
  bandText: {
    slip: 'You slipped. No game this time. Small XP. The task stays in your backlog.',
    hard: 'Tough start. Harder game, more distractions and less Focus.',
    normal: 'A normal run. Clear the task to win!',
    easy: 'Smooth start. An easier game, fewer distractions and more Focus.',
    flow: 'You are in the flow! The task is cancelled for you. Half XP, no stars.',
  },

  /* minigame */
  game: {
    quit: 'QUIT', focus: 'FOCUS', combo: 'COMBO', progress: 'PROGRESS',
    how: 'Press SPACE or click when the marker is in the GREEN zone. Gold = PERFECT. Red = trap!',
    perfect: 'PERFECT!', good: 'GOOD', miss: 'MISS',
  },
  popup: {
    alert: 'DISTRACTION!',
    tap: 'TAP IT!', hold: 'HOLD IT DOWN!', swipe: 'SWIPE SIDEWAYS!', triple_tap: 'TAP 3 TIMES!',
    nice: 'NICE! DISMISSED', fail: 'TOO SLOW!', damage: (n) => '-' + n + ' FOCUS', step: (a, b) => a + '/' + b,
  },
  effect: {
    PHONE: 'PHONE BUZZ! The green zone shrinks.',
    SOCIAL: 'CHAT PING! The green zone shrinks.',
    SCREEN: 'SCREEN TRAP! Tap it 3 times to close.',
    URGE: 'URGE! Your next hit must be PERFECT.',
    SPACE: 'CLUTTER! Junk covers the track.',
  },
  tapToClose: 'TAP TO CLOSE',

  /* results */
  result: {
    cleared: 'TASK CLEARED!', failed: 'TASK FAILED', flow: 'IN THE FLOW!', slip: 'YOU SLIPPED',
    clearedText: 'Great work! Your task is done.',
    failedText: 'You ran out of Focus. The task goes to your backlog.',
    flowText: 'The task is cancelled for you. Half XP, no stars.',
    slipText: 'No game this time. The task stays in your backlog.',
    xp: 'XP', coins: 'COINS', stars: 'STARS', band: 'ROLL', cont: 'CONTINUE',
  },

  /* messages: server error codes -> words (see GameRuleException and the controllers) */
  error: {
    credentials: 'WRONG EMAIL OR PASSWORD',
    already_rolled: 'ALREADY ROLLED',
    bad_result: 'SOMETHING WENT WRONG',
    block_has_no_task: 'THAT BLOCK HAS NO TASK',
    block_not_found: 'BLOCK NOT FOUND',
    block_used: 'BLOCK ALREADY USED',
    event_not_available: 'SOMETHING WENT WRONG',
    session_already_active: 'FINISH YOUR CURRENT TASK FIRST',
    session_not_playing: 'THIS TASK IS NOT RUNNING',
    task_already_in_a_block: 'TASK IS ALREADY IN A BLOCK',
    task_not_available: 'TASK NOT AVAILABLE',
    type_already_chosen: 'TYPE ALREADY CHOSEN',
    name: 'ENTER A NAME',
    email: 'ENTER A VALID EMAIL',
    emailTaken: 'EMAIL ALREADY USED',
    password: 'PASSWORD: 6+ LETTERS',
    generic: 'SOMETHING WENT WRONG',
    network: 'NO CONNECTION',
  },
};

/** Words for a failed server answer r = { ok, status, data }. */
PQ.errorText = function (r) {
  const E = PQ.T.error, d = r && r.data;
  if (!r || r.status === 0) return E.network;
  if (d && d.error && E[d.error]) return E[d.error];
  if (d && d.errors) {                                   // Laravel validation answer
    const field = Object.keys(d.errors)[0];
    if (field === 'email' && /taken|already/i.test(String(d.errors.email[0]))) return E.emailTaken;
    if (E[field]) return E[field];
  }
  if (r.status === 419) return 'PAGE EXPIRED. RELOAD';
  return E.generic;
};
