# Hacker Experience Legacy

This is the source-code for Legacy, the first version of Hacker Experience I built from 2012-2014 and published on 2014. I made a promise I'd release it and here it is.

Legacy reached the 1-million registered players milestone 5 years after it was released, and soon after I decided to shut it down, since I no longer could maintain it. [Context about why I decided to shut it down](https://medium.com/@renatomassaro/updates-on-hacker-experience-legacy-eb5a9e0aee33).

If you were one of these players, I hope you had a good time with Legacy! I also hope that, by releasing its code, someone else can maintain a server on which you can keep playing it.

## Disclaimer

Legacy was my first programming project. I learned to code by building Legacy. As such, its codebase is *terrible*. It has no tests, no architecture, virtually no documentation and no warranties that it will work as expected, or in a secure manner. In fact, it's more likely it will *not* work as expected. You've been warned.

## Documentation

The closest I have to a documentation can be found at the `info/` folder. Keep in mind it might be outdated, but it's better than nothing.

## Comments

I did a quick code-review and added some comments that might help you understand the code. I also added translations to comments in Portuguese (except for the ones I had no idea what I wrote originally). All my comments are prefixed with `2019: `.

## 2026 modernization

The code has since been updated to run on current software and to fix the security problems of the original release. Game rules and content are unchanged. In short:

- **PHP 8.2+**, dependencies managed with **Composer** (PHPMailer, HTMLPurifier, phpdotenv) instead of copies bundled in the repository.
- **Python 3** for the cron/generator scripts (PyMySQL instead of MySQLdb).
- **Configuration and secrets in `.env`** (see `.env.example`); nothing is hard-coded and nothing depends on `/var/www`.
- **Security fixes**:
  - All SQL is parameterized (`SqlQuery`).
  - CSRF tokens on every POST, and `SameSite=Strict` session cookies.
  - `password_hash()` with transparent upgrade of old hashes.
  - Login rate limiting.
  - Random, expiring and hashed password-reset and "remember me" tokens.
  - Output escaping for user-controlled text.
  - No more `unserialize()` of cookies, and generated pages are never executed as PHP.
  - Shell arguments are escaped.
  - A rewritten image upload (the old one allowed remote code execution).
  - Security headers, including a Content-Security-Policy.
  - Internal files are blocked from the web.
  - Updated jQuery and jQuery UI.
- **No real-world monetization**: the premium store, PayPal and Pagar.me integrations, ads and paid perks were removed (webservers are available to everyone).
- **Removed**:
  - The end-of-life bundled phpBB 3.0 forum and DokuWiki engine. The wiki pages are kept in `wiki/pages`; see `FORUM_URL`/`WIKI_URL`.
  - Facebook/Twitter login, whose APIs no longer work.
- A Docker setup, a smoke test (`tests/smoke.sh`) and GitHub Actions CI.

## Setup

### With Docker (recommended)

```
cp .env.example .env
# edit .env: set DB_PASSWORD, DB_ROOT_PASSWORD and APP_KEY (php -r "echo bin2hex(random_bytes(32));")
docker compose up -d --build
```

The game is served on http://localhost:8080. On the first start the database is created from `game.sql` and round 1 is scheduled; the `cron` container starts it about a minute later (this generates the NPC world). Put a TLS-terminating reverse proxy in front of it for a public server.

### Manual installation

Requirements: PHP 8.2+ with the `pdo_mysql`, `gd`, `gettext` and `sodium` extensions, Composer, MariaDB 10.6+ (MySQL 8 is not supported: the code uses `rank` as a column name), Python 3.10+ and Apache with `mod_rewrite` (or nginx, see `docs/nginx.conf.example`). The en_US and pt_BR locales should be installed for translations.

1. `composer install --no-dev`
2. `pip install -r python/requirements.txt`
3. `cp .env.example .env` and fill it in.
4. Create the database and schedule the first round:
   ```
   mysql -u root -p -e "CREATE DATABASE game CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   mysql -u root -p game < game.sql
   mysql -u root -p game -e "INSERT INTO round (name, startDate, status) VALUES ('Round 1', NOW(), 0)"
   ```
5. Serve the repository root with Apache (the included `.htaccess` blocks internal files and enables extension-less URLs). The web server needs write access to `html/`, `images/profile/`, `images/clan/` and `status/queries.txt`.
6. Install the scheduled jobs from `crontab` (edit the `GAME` path first). `cron2/newRoundUpdater.py` starts the round within a minute.

For local development you can also use PHP's built-in server: `php -S 127.0.0.1:8080 scripts/dev-router.php`.

### Upgrading an existing Legacy database

Back it up, then run `scripts/migrations/2026-09-modernize.sql`. Existing passwords keep working.

### Tests

`tests/smoke.sh http://127.0.0.1:8080` registers a player, logs in, visits the main pages and checks the security basics against a running server. CI (`.github/workflows/ci.yml`) runs it on every push, together with PHP linting on 8.2–8.4, `composer audit`, a Python compile check and a Docker build.

### Backups

`scripts/backup.sh` dumps the database (and optionally uploads it with the AWS CLI when `BACKUP_S3_URI` is set). It is scheduled in `crontab`.

If you have questions and/or need help setting it up or understanding its code, please open an issue. Your question may be someone else's, so do not hesitate asking it. I can't guarantee fast responses, but I'll try to help you as soon as possible.

Scan the code for `2019` to find the original author's notes.

## Security

The original code was written by someone learning to program. The 2026 pass fixed the problems listed above, but this is still a large legacy codebase without automated unit tests, so treat it accordingly: keep `APP_DEBUG=false`, serve it over HTTPS, and keep the database and cron containers off the public network. Please report vulnerabilities privately to the repository owner rather than in a public issue.

## License

Legacy is published under the MIT license, as described in the `LICENSE` file. You can run your own private game server and display ads or charge money however you like, with no ties to me and/or Neoart Labs.

The MIT license does not give you the right to use the brand Hacker Experience commercially, which is a registered trademark. In other words, please do not name your game server "Hacker Experience Continued" or anything like that. As always, [fair usage](https://support.google.com/legal/answer/4558992?hl=en), like mentioning it's based on Legacy, is OK.

## Images

I did not include with the source code most of the images and icons used in the game. You should get them yourself and make sure you understand the attribution requirements.

Most of the game icons were from the amazing [famfamfam](http://www.famfamfam.com/lab/icons/silk/) iconset. You can use it as long as you credit it.

## Credits

The original Legacy had a credits section at the bottom of the page, which is included in the source code. Please make sure to update it accordingly.

Please make sure to remove any comment or phrase that could be understood as an endorsement from myself or Neoart Labs.

## Affiliation disclaimer

All game servers that are based off of Legacy's codebase are in no way affiliated, endorsed or recommended by myself or Neoart Labs.

## Data disclaimer

I did not and I will not release the database contents from Legacy (including registered players' emails and usernames). In fact the last backup I had has been destroyed for good.

## Limitation of liability

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
