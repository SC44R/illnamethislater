"""
Makes the PLACEHOLDER pictures for Sprint 1 (pixel style, one file per entry in the Art Assets list).
Run:  python tools/make_placeholders.py      (needs:  pip install pillow)
Replace any file with final art of the same name and the game will use it. See public/assets/README_ASSETS.md.
"""
import os, math, shutil, sys
from PIL import Image, ImageDraw, ImageFont

OUT = os.path.join(os.path.dirname(__file__), '..', 'public', 'assets')
ROOM_SRC = sys.argv[1] if len(sys.argv) > 1 else None

def font(size, bold=True):
    for name in (['DejaVuSans-Bold.ttf', 'arialbd.ttf'] if bold else ['DejaVuSans.ttf', 'arial.ttf']):
        try:
            return ImageFont.truetype(name, size)
        except OSError:
            pass
    return ImageFont.load_default()

def path(folder, name):
    d = os.path.join(OUT, folder)
    os.makedirs(d, exist_ok=True)
    return os.path.join(d, name)

def grid(w, h):
    return Image.new('RGBA', (w, h), (0, 0, 0, 0))

def save(img, folder, name, scale=4):
    img.resize((img.width * scale, img.height * scale), Image.NEAREST).save(path(folder, name))

def px(d, x, y, c, w=1, h=1):
    d.rectangle([x, y, x + w - 1, y + h - 1], fill=c)

def text_center(img, s, size, fill, dy=0, bold=True):
    d = ImageDraw.Draw(img)
    f = font(size, bold)
    box = d.textbbox((0, 0), s, font=f)
    d.text(((img.width - (box[2] - box[0])) / 2 - box[0], (img.height - (box[3] - box[1])) / 2 - box[1] + dy), s, font=f, fill=fill)

WHITE, BLACK, GREY, DARK = (255, 255, 255), (30, 30, 40), (150, 155, 165), (40, 36, 60)
GOLD, RED, GREEN, BLUE, ORANGE, PURPLE, TEAL, PINK, BROWN = (255, 211, 107), (200, 70, 70), (70, 170, 110), (70, 120, 210), (235, 140, 60), (130, 90, 200), (60, 170, 170), (230, 120, 170), (140, 90, 50)

# ---------------------------------------------------------------- small icons (16x16 grid, x4 = 64px)
def icon(name, drawer, folder='ui', bg=None):
    g = grid(16, 16)
    d = ImageDraw.Draw(g)
    if bg:
        d.rounded_rectangle([0, 0, 15, 15], 3, fill=bg)
    drawer(d)
    save(g, folder, name)

def heart(d, c=RED):
    d.ellipse([1, 2, 8, 9], fill=c); d.ellipse([7, 2, 14, 9], fill=c); d.polygon([(1, 7), (14, 7), (7, 14)], fill=c)

def star(d, c=GOLD):
    pts = []
    for k in range(10):
        r = 7.5 if k % 2 == 0 else 3.2
        a = math.radians(-90 + 36 * k)
        pts.append((8 + r * math.cos(a), 8.5 + r * math.sin(a)))
    d.polygon(pts, fill=c)

def flame(d):
    for (x, y, w, c) in [(7, 1, 2, ORANGE), (6, 3, 4, ORANGE), (5, 5, 6, ORANGE), (4, 7, 8, RED), (4, 9, 8, RED), (5, 11, 6, GOLD), (6, 13, 4, GOLD)]:
        px(d, x, y, c, w, 2)
def moon(d):
    d.ellipse([3, 2, 13, 14], fill=GOLD); d.ellipse([6, 0, 16, 12], fill=(0, 0, 0, 0))

def tree(d):
    px(d, 7, 10, BROWN, 2, 5)
    for (x, y, w) in [(7, 1, 2), (5, 3, 6), (4, 5, 8), (3, 7, 10), (2, 9, 12)]:
        px(d, x, y, GREEN, w, 2)
def sword(d):
    px(d, 7, 1, (220, 225, 235), 2, 9); px(d, 4, 10, GOLD, 8, 1); px(d, 7, 11, BROWN, 2, 3); px(d, 6, 14, GOLD, 4, 1)
def coin(d):
    d.ellipse([2, 2, 13, 13], fill=GOLD, outline=(190, 140, 40)); px(d, 7, 5, (190, 140, 40), 2, 6)
def check(d):
    for i in range(3): px(d, 2 + i, 8 + i, GREEN, 2, 2)
    for i in range(7): px(d, 5 + i, 10 - i, GREEN, 2, 2)
def play(d):
    d.polygon([(5, 2), (13, 8), (5, 14)], fill=GOLD)

def user(d):
    d.ellipse([5, 1, 10, 6], fill=(240, 200, 160)); d.rounded_rectangle([3, 8, 12, 14], 2, fill=BLUE)
def key(d):
    d.ellipse([2, 3, 7, 8], outline=GOLD, width=2); px(d, 7, 5, GOLD, 7, 2); px(d, 11, 7, GOLD, 2, 3); px(d, 14, 7, GOLD, 1, 2)
def sparkle(d):
    px(d, 7, 1, GOLD, 2, 14); px(d, 1, 7, GOLD, 14, 2); px(d, 5, 5, GOLD, 6, 6)
def back(d):
    for i in range(5): px(d, 3 + i, 7 - i, WHITE, 2, 1); px(d, 3 + i, 8 + i, WHITE, 2, 1)
    px(d, 3, 7, WHITE, 11, 2)
def pile(d):
    for i, c in enumerate([(240, 235, 215), (225, 220, 200), (240, 235, 215)]):
        px(d, 3 + (i % 2) * 2, 9 - i * 3, c, 9, 3); px(d, 3 + (i % 2) * 2, 9 - i * 3, GREY, 9, 1)
def calendar(d):
    px(d, 2, 3, WHITE, 12, 11); px(d, 2, 3, RED, 12, 3); px(d, 4, 1, DARK, 2, 3); px(d, 10, 1, DARK, 2, 3)
    for r in range(2):
        for c in range(3): px(d, 4 + c * 3, 8 + r * 3, GREY, 2, 2)
def warn(d):
    d.polygon([(8, 1), (15, 14), (1, 14)], fill=ORANGE); px(d, 7, 5, WHITE, 2, 5); px(d, 7, 11, WHITE, 2, 2)
def pip_full(d): d.rectangle([4, 4, 11, 11], fill=GOLD)
def pip_empty(d): d.rectangle([4, 4, 11, 11], outline=GREY)
def willpower(d):
    px(d, 4, 4, ORANGE, 8, 8); px(d, 4, 4, (255, 180, 100), 8, 2); px(d, 6, 12, ORANGE, 4, 2)
def leaf(d):
    d.polygon([(3, 13), (3, 6), (9, 2), (13, 3), (12, 9), (7, 13)], fill=GREEN); px(d, 4, 12, (30, 100, 60), 7, 1)
def drive(d):
    for i in range(5): px(d, 7 - i, 3 + i, TEAL, 2 + 2 * i, 1)
    px(d, 6, 8, TEAL, 4, 6)
def dumbbell(d):
    px(d, 2, 5, GREY, 2, 6); px(d, 4, 4, DARK, 2, 8); px(d, 6, 7, GREY, 4, 2); px(d, 10, 4, DARK, 2, 8); px(d, 12, 5, GREY, 2, 6)
def book(d):
    px(d, 2, 3, BLUE, 12, 10); px(d, 3, 4, WHITE, 5, 8); px(d, 9, 4, WHITE, 4, 8); px(d, 8, 3, DARK, 1, 10)
def cards(d):
    px(d, 2, 4, PINK, 7, 9); px(d, 7, 3, TEAL, 7, 9); px(d, 8, 4, WHITE, 5, 7)
def laptop(d):
    px(d, 2, 3, DARK, 12, 8); px(d, 3, 4, (120, 180, 255), 10, 6); px(d, 1, 11, GREY, 14, 2)
def bed(d):
    px(d, 1, 6, BROWN, 14, 3); px(d, 2, 5, WHITE, 4, 2); px(d, 6, 6, BLUE, 8, 3); px(d, 1, 9, BROWN, 2, 4); px(d, 13, 9, BROWN, 2, 4)
def tap(d):
    d.ellipse([3, 3, 12, 12], outline=WHITE, width=1); px(d, 7, 4, GOLD, 2, 6); px(d, 6, 9, GOLD, 4, 4)
def hold(d):
    d.ellipse([3, 3, 12, 12], outline=WHITE, width=1); px(d, 7, 4, GOLD, 2, 6); px(d, 6, 9, GOLD, 4, 4); d.arc([0, 0, 15, 15], 200, 340, fill=GOLD, width=1)
def swipe(d):
    px(d, 2, 7, WHITE, 11, 2)
    for i in range(4): px(d, 9 + i, 4 + i, WHITE, 2, 1); px(d, 9 + i, 11 - i, WHITE, 2, 1)
def triple(d):
    for x in (1, 6, 11): d.ellipse([x, 5, x + 3, 8], fill=GOLD)
    px(d, 1, 10, WHITE, 14, 1)

ui = {'icon_user': user, 'icon_key': key, 'icon_play': play, 'icon_new': sparkle, 'icon_back': back, 'icon_task': sword, 'icon_free': tree,
      'icon_check': check, 'icon_coin': coin, 'icon_xp': star, 'icon_focus': heart, 'icon_combo': flame, 'icon_pile': pile,
      'icon_calendar': calendar, 'icon_warn': warn, 'icon_sleep': moon, 'pip_full': pip_full, 'pip_empty': pip_empty, 'star_full': star,
      'hotspot_laptop': laptop, 'hotspot_bed': bed}
for n, f in ui.items():
    icon(n + '.png', f)
g = grid(16, 16); star(ImageDraw.Draw(g), (80, 80, 95)); save(g, 'ui', 'star_empty.png')
for n, f in {'stat_focus': heart, 'stat_willpower': willpower, 'stat_calm': leaf, 'stat_drive': drive}.items():
    icon(n + '.png', f, 'stats')
for n, f in {'type_practice': dumbbell, 'type_deep': book, 'type_memorize': cards}.items():
    icon(n + '.png', f, 'tasktypes')
for n, f in {'prompt_tap': tap, 'prompt_hold': hold, 'prompt_swipe': swipe, 'prompt_triple_tap': triple}.items():
    icon(n + '.png', f, 'distractions', bg=(50, 45, 80))

# ---------------------------------------------------------------- procrastinator type icons and avatars
types = {'avoider': (GREEN, (90, 60, 40)), 'perfectionist': (BLUE, (40, 30, 30)), 'crammer': (ORANGE, (200, 150, 60)), 'daydreamer': (PURPLE, (150, 90, 60))}
def type_icon(d, kind, c):
    if kind == 'avoider':   # turtle
        d.ellipse([2, 5, 11, 13], fill=c); px(d, 11, 8, (120, 200, 140), 3, 3); px(d, 3, 13, (40, 100, 60), 2, 2); px(d, 9, 13, (40, 100, 60), 2, 2)
    elif kind == 'perfectionist':   # target
        d.ellipse([1, 1, 14, 14], fill=WHITE); d.ellipse([3, 3, 12, 12], fill=c); d.ellipse([5, 5, 10, 10], fill=WHITE); d.ellipse([7, 7, 8, 8], fill=RED)
    elif kind == 'crammer':   # clock with a bolt
        d.ellipse([1, 1, 14, 14], fill=WHITE, outline=c, width=2); px(d, 7, 3, DARK, 1, 5); px(d, 7, 7, DARK, 4, 1)
    else:   # cloud
        d.ellipse([1, 6, 7, 12], fill=WHITE); d.ellipse([4, 3, 11, 11], fill=WHITE); d.ellipse([8, 6, 14, 12], fill=WHITE); px(d, 2, 10, WHITE, 12, 3)
for k, (c, hair) in types.items():
    g = grid(16, 16); d = ImageDraw.Draw(g); d.rounded_rectangle([0, 0, 15, 15], 3, fill=(50, 45, 80)); type_icon(d, k, c); save(g, 'types', f'type_{k}.png')
    # avatar: simple person, shirt colour = type colour
    a = grid(16, 24); d = ImageDraw.Draw(a)
    px(d, 5, 1, hair, 6, 3); px(d, 5, 3, (240, 200, 160), 6, 5); px(d, 6, 5, DARK, 1, 1); px(d, 9, 5, DARK, 1, 1)
    px(d, 4, 9, c, 8, 8); px(d, 2, 9, c, 2, 6); px(d, 12, 9, c, 2, 6); px(d, 5, 17, (60, 60, 90), 3, 6); px(d, 8, 17, (60, 60, 90), 3, 6)
    save(a, 'types', f'avatar_{k}_idle.png')

# ---------------------------------------------------------------- causes C1..C11 (64x64 tiles)
causes = [('C1', 'fog', (150, 160, 180)), ('C2', 'mountain', (110, 120, 150)), ('C3', 'diamond', (120, 100, 200)), ('C4', 'sigh', (190, 130, 90)),
          ('C5', 'far clock', (80, 150, 190)), ('C6', 'phone', (70, 120, 210)), ('C7', 'screen', (170, 80, 140)), ('C8', 'bubbles', (110, 180, 170)),
          ('C9', 'battery', (200, 90, 80)), ('C10', 'noise', (200, 160, 70)), ('C11', 'broom', (120, 170, 90))]
def cause_shape(d, i):
    W = WHITE
    if i == 0:   # fog
        d.ellipse([6, 10, 18, 20], fill=W); d.ellipse([12, 6, 26, 20], fill=W); d.ellipse([20, 10, 30, 20], fill=W)
    elif i == 1: d.polygon([(4, 24), (14, 6), (20, 16), (24, 10), (30, 24)], fill=W)
    elif i == 2: d.polygon([(16, 3), (28, 14), (16, 26), (4, 14)], fill=W)
    elif i == 3: d.ellipse([6, 6, 26, 26], fill=W); px(d, 11, 12, DARK, 3, 3); px(d, 18, 12, DARK, 3, 3); px(d, 12, 20, DARK, 8, 2)
    elif i == 4: d.ellipse([4, 4, 28, 28], fill=W); px(d, 15, 8, DARK, 2, 9); px(d, 15, 15, DARK, 7, 2)
    elif i == 5: d.rounded_rectangle([9, 3, 23, 28], 3, fill=W); px(d, 11, 6, DARK, 10, 15)
    elif i == 6: px(d, 4, 6, W, 24, 16); px(d, 6, 8, DARK, 20, 12); px(d, 11, 24, W, 10, 3); px(d, 14, 12, GOLD, 5, 4)
    elif i == 7:
        for (x, y, r) in [(9, 18, 4), (18, 10, 5), (22, 22, 3)]: d.ellipse([x - r, y - r, x + r, y + r], outline=W, width=2)
    elif i == 8: px(d, 5, 9, W, 21, 14); px(d, 26, 12, W, 3, 8); px(d, 7, 11, RED, 4, 10)
    elif i == 9:
        for k in range(3): d.arc([6 + k * 4, 8, 14 + k * 4, 24], 300, 60, fill=W, width=2)
        px(d, 5, 12, W, 4, 8)
    else: px(d, 14, 3, BROWN, 3, 14); d.polygon([(8, 17), (24, 17), (27, 28), (5, 28)], fill=W)
for i, (code, label, col) in enumerate(causes):
    g = grid(32, 32); d = ImageDraw.Draw(g); d.rounded_rectangle([0, 0, 31, 31], 5, fill=col); cause_shape(d, i)
    big = g.resize((64, 64), Image.NEAREST); ImageDraw.Draw(big).text((4, 2), code, font=font(11), fill=WHITE)
    big.save(path('causes', f'cause_{code.lower()}.png'))

# ---------------------------------------------------------------- distraction creatures (32x32 grid, x4 = 128px)
def eyes(d, x, y, sleepy=False):
    px(d, x, y, WHITE, 4, 4); px(d, x + 7, y, WHITE, 4, 4)
    px(d, x + 1, y + 1, BLACK, 2, 2); px(d, x + 8, y + 1, BLACK, 2, 2)
    if sleepy: px(d, x, y, (90, 80, 120), 4, 2); px(d, x + 7, y, (90, 80, 120), 4, 2)
def creature(name, drawer):
    g = grid(32, 32); drawer(ImageDraw.Draw(g)); save(g, 'distractions', f'creature_{name}.png')
def c_phone(d):
    d.rounded_rectangle([9, 3, 23, 29], 3, fill=(70, 120, 210)); px(d, 11, 6, (30, 40, 80), 10, 16); eyes(d, 12, 11); px(d, 13, 18, WHITE, 6, 1)
    for i in range(3): px(d, 4 - i, 10 + i * 4, GOLD, 2, 3); px(d, 26 + i, 10 + i * 4, GOLD, 2, 3)
def c_episode(d):
    px(d, 4, 6, (170, 80, 140), 24, 18); px(d, 6, 8, (40, 30, 60), 20, 14); eyes(d, 9, 11)
    d.polygon([(14, 19), (14, 24), (19, 21)], fill=GOLD); px(d, 10, 24, GREY, 12, 2); px(d, 8, 26, GREY, 16, 2)
def c_later(d):
    d.ellipse([3, 3, 28, 28], fill=(80, 150, 190), outline=WHITE, width=2); eyes(d, 10, 11, sleepy=True); px(d, 14, 19, WHITE, 5, 1)
    px(d, 22, 2, WHITE, 5, 1); px(d, 24, 3, WHITE, 2, 1); px(d, 22, 5, WHITE, 5, 1)
def c_noisy(d):
    px(d, 6, 9, (200, 160, 70), 10, 14); d.polygon([(16, 9), (24, 4), (24, 28), (16, 23)], fill=(220, 180, 90)); eyes(d, 6, 12)
    for k in range(3): d.arc([20 + k * 3, 8 - k, 28 + k * 3, 24 + k], 300, 60, fill=WHITE, width=1)
for n, f in {'phone_buzz': c_phone, 'just_one_episode': c_episode, 'later': c_later, 'noisy_room': c_noisy}.items():
    creature(n, f)
g = grid(24, 24); d = ImageDraw.Draw(g); d.rounded_rectangle([0, 0, 23, 23], 3, fill=(36, 32, 60), outline=GOLD, width=1); save(g, 'distractions', 'popup_frame.png')

# ---------------------------------------------------------------- dice
def d20_face(num, size=128, rot=0, label=None):
    im = Image.new('RGBA', (size, size), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    pts = [(size / 2 + 56 * math.cos(math.radians(60 * k - 90 + rot)), size / 2 + 56 * math.sin(math.radians(60 * k - 90 + rot))) for k in range(6)]
    d.polygon(pts, fill=(235, 225, 200), outline=(90, 60, 40))
    inner = [(size / 2 + 36 * math.cos(math.radians(60 * k - 90 + rot + 30)), size / 2 + 36 * math.sin(math.radians(60 * k - 90 + rot + 30))) for k in range(6)]
    d.polygon(inner, outline=(190, 170, 140))
    if rot == 0:
        text_center(im, label or str(num), 42 if len(label or str(num)) < 3 else 30, (90, 40, 40))
    return im
for n in range(1, 21):
    d20_face(n).save(path('dice', f'd20_face_{n:02d}.png'))
for k in range(8):
    d20_face(0, rot=k * 40, label='?').save(path('dice', f'd20_tumble_{k + 1}.png'))
tray = Image.new('RGBA', (256, 96), (0, 0, 0, 0)); ImageDraw.Draw(tray).ellipse([4, 8, 252, 92], fill=(70, 45, 30), outline=(40, 25, 15), width=3); tray.save(path('dice', 'dice_tray.png'))

# ---------------------------------------------------------------- band icons (96x96)
def band(name, col, drawer):
    im = Image.new('RGBA', (96, 96), (0, 0, 0, 0)); d = ImageDraw.Draw(im); d.ellipse([4, 4, 92, 92], fill=col, outline=WHITE, width=4); drawer(d); im.save(path('bands', f'band_{name}.png'))
band('slip', (190, 70, 70), lambda d: (d.line([(30, 30), (66, 66)], fill=WHITE, width=9), d.line([(66, 30), (30, 66)], fill=WHITE, width=9)))
band('hard', (225, 130, 60), lambda d: d.polygon([(48, 22), (72, 68), (24, 68)], fill=WHITE))
band('normal', (230, 200, 90), lambda d: (d.rectangle([28, 36, 68, 44], fill=WHITE), d.rectangle([28, 52, 68, 60], fill=WHITE)))
band('easy', (120, 200, 130), lambda d: (d.line([(28, 56), (48, 36), (68, 56)], fill=WHITE, width=9)))
band('flow', (60, 170, 170), lambda d: d.polygon([(48, 18), (57, 39), (79, 39), (61, 53), (68, 76), (48, 62), (28, 76), (35, 53), (17, 39), (39, 39)], fill=WHITE))

# ---------------------------------------------------------------- scenes and banners
sc = Image.new('RGBA', (256, 192), (70, 60, 100, 255)); d = ImageDraw.Draw(sc)
d.rectangle([60, 40, 196, 160], fill=WHITE); d.rectangle([60, 40, 196, 70], fill=RED)
for r in range(3):
    for c in range(4): d.rectangle([76 + c * 30, 84 + r * 24, 92 + c * 30, 98 + r * 24], fill=(200, 200, 210))
d.line([(70, 80), (186, 150)], fill=(200, 40, 40), width=10); d.line([(186, 80), (70, 150)], fill=(200, 40, 40), width=10)
sc.save(path('scenes', 'scene_task_cancelled.png'))
sc = Image.new('RGBA', (256, 192), (60, 60, 80, 255)); d = ImageDraw.Draw(sc)
d.ellipse([50, 40, 130, 100], fill=(140, 145, 160)); d.ellipse([90, 30, 190, 100], fill=(140, 145, 160)); d.ellipse([140, 50, 210, 100], fill=(140, 145, 160)); d.rectangle([60, 80, 200, 104], fill=(140, 145, 160))
for x in (80, 120, 160): d.line([(x, 115), (x - 10, 150)], fill=(110, 160, 230), width=5)
sc.save(path('scenes', 'scene_slip_generic.png'))
for name, col, drawer in [('cleared', (60, 150, 100), lambda d: [d.polygon([(48 + 40 * k, 14), (54 + 40 * k, 30), (72 + 40 * k, 30), (58 + 40 * k, 40), (63 + 40 * k, 56), (48 + 40 * k, 46), (33 + 40 * k, 56), (38 + 40 * k, 40), (24 + 40 * k, 30), (42 + 40 * k, 30)], fill=GOLD) for k in range(3)]),
                          ('failed', (170, 70, 70), lambda d: (d.line([(110, 20), (146, 76)], fill=WHITE, width=10), d.line([(146, 20), (110, 76)], fill=WHITE, width=10)))]:
    im = Image.new('RGBA', (256, 96), (0, 0, 0, 0)); d = ImageDraw.Draw(im); d.rounded_rectangle([2, 2, 253, 93], 12, fill=col, outline=WHITE, width=4); drawer(d); im.save(path('results', f'banner_{name}.png'))

# ---------------------------------------------------------------- logo and favicon
lg = Image.new('RGBA', (256, 64), (0, 0, 0, 0)); d = ImageDraw.Draw(lg)
d.text((6, 6), 'PROCRASTINATION', font=font(24), fill=GOLD); d.text((6, 34), 'QUEST', font=font(24), fill=(190, 140, 255))
lg.save(path('ui', 'logo_title.png'))
g = grid(16, 16); d = ImageDraw.Draw(g); d.rounded_rectangle([0, 0, 15, 15], 3, fill=(50, 45, 80)); star(d); save(g, 'ui', 'favicon.png', 2)

# ---------------------------------------------------------------- the room (uses YOUR room picture)
room_dir = path('room', 'room_base.png')
src = ROOM_SRC or os.path.join(os.path.dirname(__file__), 'room_base.png')
if os.path.exists(src):
    shutil.copy(src, room_dir)
    print('room picture copied')
elif os.path.exists(room_dir):
    print('keeping the existing room picture')
else:
    im = Image.new('RGB', (1024, 572), (120, 90, 70)); text_center(im, 'PUT YOUR ROOM PICTURE HERE (room_base.png, 1024 x 572)', 28, WHITE); im.save(room_dir)
    print('no room picture found, made a plain stand-in')
print('placeholders done')
