# Placeholder pictures (Sprint 1)

Every picture here is a **placeholder** made by `tools/make_placeholders.py` (pixel style, one file per entry in the
*Art Assets* sheet of the project plan). The **room picture is yours**. Everything else should be replaced by final art.

**How to replace a picture:** draw the new one, save it with the **same file name** in the **same folder**, and refresh
the browser. No code changes are needed. Keep the same size (or the same proportions) so layouts do not break.

**Tip:** keep one art style for everything. Record the license of any free asset you use in the README.

| Folder | Size (px) | Files |
|---|---|---|
| `bands/` | 96 x 96 | band_easy.png, band_flow.png, band_hard.png, band_normal.png, band_slip.png |
| `causes/` | 64 x 64 | cause_c1.png, cause_c10.png, cause_c11.png, ... (11 files) |
| `dice/` | 128 x 128 | d20_face_01.png, d20_face_02.png, d20_face_03.png, ... (28 files) |
| `dice/` | 256 x 96 | dice_tray.png |
| `distractions/` | 128 x 128 | creature_just_one_episode.png, creature_later.png, creature_noisy_room.png, creature_phone_buzz.png |
| `distractions/` | 96 x 96 | popup_frame.png |
| `distractions/` | 64 x 64 | prompt_hold.png, prompt_swipe.png, prompt_tap.png, prompt_triple_tap.png |
| `results/` | 256 x 96 | banner_cleared.png, banner_failed.png |
| `room/` | 1024 x 682 | room_base.png |
| `scenes/` | 256 x 192 | scene_slip_generic.png, scene_task_cancelled.png |
| `stats/` | 64 x 64 | stat_calm.png, stat_drive.png, stat_focus.png, stat_willpower.png |
| `tasktypes/` | 64 x 64 | type_deep.png, type_memorize.png, type_practice.png |
| `types/` | 64 x 96 | avatar_avoider_idle.png, avatar_crammer_idle.png, avatar_daydreamer_idle.png, avatar_perfectionist_idle.png |
| `types/` | 64 x 64 | type_avoider.png, type_crammer.png, type_daydreamer.png, type_perfectionist.png |
| `ui/` | 32 x 32 | favicon.png |
| `ui/` | 64 x 64 | hotspot_bed.png, hotspot_laptop.png, icon_back.png, ... (22 files) |
| `ui/` | 256 x 64 | logo_title.png |

## What each folder is used for

| Folder | Used in | Feature |
|---|---|---|
| `room/` | The room hub and the login zoom | All |
| `ui/` | Icons on the laptop screen, planner, HUD, results | F1, F2, F3 |
| `stats/` | Focus, Willpower, Calm and Drive icons | F1 |
| `types/` | Procrastinator type icons and avatars | F1 |
| `causes/` | The 11 cause icons in the planner | F2 |
| `tasktypes/` | Practice, deep work, memorize icons | F2 |
| `dice/` | The d20 faces, tumble frames and tray | F4 |
| `bands/` | Slip, Hard, Normal, Easy, Flow icons | F4 |
| `scenes/` | Task cancelled and Slip scenes | F4 |
| `distractions/` | The 4 starter creatures, pop-up frame, tap/hold/swipe icons | F5 |
| `results/` | Cleared and failed banners | F3 |

## Still missing (planned for later sprints)
Focus Tuner and Memory Match art (Sprint 2), cards (Sprint 2 and 3), gear and Bestiary creatures (Sprint 3), den items and skill tree (Sprint 4).

## Font
Text uses **Press Start 2P** (SIL Open Font License, see `public/fonts/OFL-PressStart2P.txt`), hosted locally in `public/fonts/`. To use another pixel font, add its file there and change the `@font-face` and `--pixel` at the top of `public/css/game.css`.
