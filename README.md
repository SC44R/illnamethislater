# illnamethislater

A browser game about beating procrastination. You play a procrastinator living in a pixel room. You plan tasks on your laptop, roll a d20 to see how the day goes, then play a rhythm minigame while distractions try to pull you off task.

Built with Laravel 12 (PHP) and MySQL. The front end is plain JavaScript and CSS, so there is nothing to build with npm.

This guide is written for Windows. Do the steps in order. Each one ends with a line saying what you should see before you move on.

## Part 1: Install the tools (one time only)

You need three programs: Git, XAMPP and Composer. Install them in this order.

### 1. Git

Download from https://git-scm.com/download/win and install with the default options (just keep clicking Next).

Open PowerShell (press the Windows key, type `powershell`, press Enter) and run:

```
git --version
```

You should see a version number. Then tell Git who you are, using the same email as your GitHub account:

```
git config --global user.name "Your Name"
git config --global user.email "you@example.com"
```

### 2. XAMPP

Download from https://www.apachefriends.org and install it. The default folder is `C:\xampp`, keep it. XAMPP gives you PHP and MySQL.

The XAMPP version must include PHP 8.2 or newer. Any download from the site right now does.

### 3. Tell Windows where PHP is

1. Press the Windows key, type `environment variables`, and open **Edit the system environment variables**.
2. Click **Environment Variables**.
3. Under **User variables**, select **Path** and click **Edit**.
4. Click **New**, type `C:\xampp\php`, then click OK on all the windows.
5. Close PowerShell and open a new one. Run:

```
php -v
```

You should see `PHP 8.2` or higher. If it says `php is not recognized`, step 4 didn't save. Redo it and open a fresh PowerShell.

### 4. Turn on the PHP extensions the game needs

1. Open `C:\xampp\php\php.ini` in Notepad.
2. Press Ctrl+F and search for each line below. If the line starts with a `;`, delete the `;`. If it already has no `;`, leave it.

```
extension=curl
extension=fileinfo
extension=mbstring
extension=openssl
extension=pdo_mysql
extension=zip
extension=pdo_sqlite
extension=sqlite3
```

3. Save the file.

### 5. Composer

1. Download the installer from https://getcomposer.org/download and run it.
2. When it asks "Choose the command-line PHP you want to use", click **Browse**, go to `C:\xampp\php`, and pick `php.exe`.
3. Keep clicking Next until it finishes.
4. Open a new PowerShell and run:

```
composer -V
```

You should see a Composer version number.

## Part 2: Get the project

Pick a folder where you keep projects, for example `C:\projects`. In PowerShell:

```
cd C:\projects
git clone https://github.com/SC44R/illnamethislater.git
cd illnamethislater
```

You are now inside the project folder. Keep this PowerShell window open, every command below is run here.

Install the project's PHP packages:

```
composer install
```

This takes a minute or two and creates a `vendor` folder. You should see no red errors at the end.

Create your own settings file and a security key:

```
copy .env.example .env
php artisan key:generate
```

You should see `Application key set successfully`.

## Part 3: Set up the database

1. Open the **XAMPP Control Panel** (search for `xampp` in the Start menu).
2. Click **Start** next to **MySQL**. Also click **Start** next to **Apache**. Both names should turn green.
3. Open http://localhost/phpmyadmin in your browser.
4. Click **New** on the left side.
5. Type the database name `illnamethislater`, choose `utf8mb4_unicode_ci` from the dropdown next to it, and click **Create**.

Now connect the project to it. Open the `.env` file in the project folder with Notepad or VS Code. (It starts with a dot. If you can't see it in Explorer, open the folder in VS Code instead.)

Find the database lines. They will look something like this:

```
DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=laravel
# DB_USERNAME=root
# DB_PASSWORD=
```

Replace them so they read exactly like this, with no `#` at the start of any line:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=illnamethislater
DB_USERNAME=root
DB_PASSWORD=
```

Save the file. Those are the XAMPP defaults (user `root`, empty password). If you changed your MySQL password, put it in `DB_PASSWORD`.

Also check that this line is there, and add it if not:

```
SESSION_DRIVER=database
```

## Part 4: Run it

Back in PowerShell, in the project folder:

```
php artisan migrate --seed
php artisan serve
```

The first command creates the tables and fills in the starting data. If it asks `Would you like to create it?` or mentions a missing database, check your database name in `.env` matches the one you made in phpMyAdmin. The second command starts the game and keeps running. Leave that window open.

Open http://127.0.0.1:8000 in your browser. You should see the pixel room with the camera zoomed in on the laptop screen, which shows a login form.

To stop the game, click the PowerShell window and press Ctrl+C. To start it again later, you only need to start MySQL in XAMPP and run `php artisan serve` from the project folder.

## Working as a team

Never work directly on `main`. For every piece of work:

```
git checkout main
git pull
git checkout -b your-branch-name
```

Make your changes. Then save and upload them:

```
git add .
git status
git commit -m "Short description of what you changed"
git push -u origin your-branch-name
```

Check `git status` before you commit. The list should only contain files you changed on purpose. `.env` and `vendor` must never appear, they are ignored automatically.

Then go to the repo page on GitHub, click **Compare & pull request**, and ask someone else in the group to review it before merging.

When you start a new day or after anyone merges something:

```
git checkout main
git pull
composer install
php artisan migrate
```

`composer install` picks up new packages and `php artisan migrate` picks up new database tables. Both do nothing if there is nothing new.


## Project layout

| Path | What is in it |
|---|---|
| `app/Support` | Game maths: dice bands, backlog penalty, distraction spacing, scoring, seeded random |
| `app/Services` | Game logic (planner, dice, sessions, rewards, stats) |
| `app/Models` | One class per table |
| `app/Http` | Thin controllers and form requests |
| `database/migrations`, `database/seeders` | Tables and starting data |
| `resources/views/room.blade.php` | The one page of the game |
| `public/js` | Browser code: room, planner, session, minigame. All player-facing text is in `text.js` |
| `public/css/game.css` | Styling |
| `public/assets` | Pictures, organised by folder |
| `tools` | Placeholder image generator and a browser smoke test |


### Changing game numbers

Balance values live in the `balance_config` table, filled from `config/game.php` by the seeder. Edit a row in phpMyAdmin and refresh. If you change `config/game.php`, reseed with:

```
php artisan db:seed --class=BalanceConfigSeeder
```

### Replacing pictures

Save the new picture with the same file name in the same folder under `public/assets` and refresh. Keep the same size or proportions. The room picture is `public/assets/room/room_base.png` (1024 x 682). If you change its size or move the laptop, update `W`, `H`, `SCREEN` and the hotspot positions in `public/js/room.js`, and the `#screen` transform in `public/css/game.css`.

## Working as a team

Don't push straight to `main`. Make a branch for what you're working on:

```
git checkout main
git pull
git checkout -b your-branch-name
```

Commit in small steps, then push and open a pull request on GitHub for someone else to review:

```
git add .
git commit -m "Short description of the change"
git push -u origin your-branch-name
```

After a pull request is merged, run `git checkout main` and `git pull` before starting the next thing. If someone added a migration, run `php artisan migrate` too. If someone added a package, run `composer install`.

## Common problems

| Problem | Fix |
|---|---|
| `could not find driver` | Enable `pdo_mysql` (and `pdo_sqlite` for tests) in `php.ini`, then open a new terminal |
| `Unknown database` | Create the database and check `DB_DATABASE` in `.env` |
| Connection refused (`2002`) | Start MySQL in the XAMPP Control Panel |
| Blank page or `419 Page Expired` | Use `php artisan serve` rather than opening files directly, and clear cookies |
| `Class "..." not found` | Run `composer dump-autoload` |
| `No application encryption key has been specified` | Run `php artisan key:generate` |
| `Module "openssl" is already loaded` | A line is duplicated in `php.ini`. Remove one copy. It is only a warning |
| Pictures missing | Regenerate placeholders with `python tools/make_placeholders.py` (needs `pip install pillow`) |

#
