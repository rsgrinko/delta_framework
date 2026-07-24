# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

Delta Framework — a custom, self-written PHP framework (not built on Laravel/Symfony/etc.). Code, comments, and
commit messages are predominantly in Russian. No frontend build system (no npm/webpack) — the public site uses
server-rendered Twig templates with a hand-written CSS design system, the admin panel uses plain PHP/jQuery pages.

## Commands

```bash
# Install dependencies
composer install

# Run all tests (as used in .gitlab-ci.yml)
vendor/bin/phpunit --testdox tests

# Run a single test file / method
vendor/bin/phpunit tests/UserTest.php
vendor/bin/phpunit --filter testGetAllUserData tests/UserTest.php

# Static analysis (dev dependency, used by .github/workflows/psalm.yml)
vendor/bin/psalm

# Database migrations (Phinx, config in phinx.php, migrations in db/migrations, seeds in db/seed)
vendor/bin/phinx migrate -e dev
vendor/bin/phinx migrate -e prod
vendor/bin/phinx seed:run -e dev

# Quick syntax/render sanity check without a browser (no test harness renders Twig otherwise):
php -l core/lib/Core/App.class.php
php -r '$root="."; require $root."/vendor/autoload.php"; $t=new Twig\Environment(new Twig\Loader\FilesystemLoader($root."/templates"),["strict_variables"=>false]); echo $t->render("index.twig", [...]);'
```

There is no local dev server script — this is a classic Apache/nginx + PHP-FPM app; `.htaccess` handles rewriting
to `index.php`. In this project's actual dev setup, edits made on disk are synced to a live `dev.it-stories.ru`
host automatically — that host is useful for verifying behavior end-to-end (e.g. via `curl` with a cookie jar to
exercise session-authenticated POST flows) when something can't be confirmed from code alone.

## Architecture

### Entry points
- `index.php` — sets `USE_ROUTER = true` and requires `core/bootstrap.php`. This is the single entry point for the
  public site (all requests are rewritten here, then dispatched by the router).
- `core/api.php` — REST API entry point. Dispatches to `Core\Api\ApiController` methods by `$_REQUEST['method']`,
  authenticates via `$_REQUEST['token']` against `User::getByToken()`, except for methods listed in `$noAuthMethods`
  (includes `createUser`/`getToken`, so account creation and login-for-a-token both work unauthenticated).
- `core/cron.php` — scans `core/cronTasks/*.php` (except `init.php`) and `exec()`s each one as a detached background
  PHP process. This is the mechanism for scheduled/background jobs, not a job scheduler library.
- `admin/index.php` and siblings (`admin/users.php`, `admin/posts.php`, etc.) — the admin panel is a separate set of
  plain PHP pages (not routed through `core/routes.php`), sharing `admin/inc/bootstrap.php`, `header.php`, `footer.php`.
  AJAX handlers live in `admin/ajax/`.

### Bootstrap and config loading order (`core/bootstrap.php`)
1. `vendor/autoload.php` (Composer PSR-4: `Core\` → `core/lib/`)
2. Optional root-level `config.local.php` if present — this is the mechanism for running multiple sites/environments
   off one codebase (mount shared `/core/`, `/uploads/`, `/admin/` and override settings per site).
3. `core/config.php` — defines all configuration as global constants, each guarded by `if (!defined(...))` so
   `config.local.php` values (defined earlier) take precedence.
4. A second, custom `spl_autoload_register` autoloader loads any `Core\*` class from `core/lib/**/*.class.php` or
   `**/*.php` — this exists alongside the Composer PSR-4 autoloader because most classes use the `.class.php` suffix,
   which Composer's PSR-4 mapping alone won't resolve.
5. If `CORE_FULL_LOAD` (i.e. not a cron process) and `USE_ROUTER === true`: initializes Twig
   (`PATH_TO_TEMPLATES` = `/templates`), requires `core/routes.php`, calls `Router::execute()`, then `die()`.

`IS_CRON_PROCESS` (if defined true before bootstrap) skips UTM tracking, cache flush/logout handling, and DDoS
protection setup — cron tasks include `bootstrap.php` with this flag set.

### Routing (`core/routes.php`, `Core\Models\Router`)
Routes are registered with `Router::route(string $pattern, $callback, bool $usePagination = false)`. `$pattern` is a
path like `/users/(\d+)` (regex groups become positional args passed to the callback). `$callback` is either a
`'\Core\App::method'` string or a closure. `$usePagination = true` additionally matches `/pattern/page/(\d+)`.
`Router::execute()` matches against `parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)` (the query string is stripped
before matching), so routes work correctly even when hit with `?foo=bar` — pagination on `/users` uses a query
string (`?page=N`) precisely because of this. Unmatched routes fall through to whatever route is registered as
`/404`. The default controller for site routes is `Core\App` (`core/lib/Core/App.class.php`); its private `render()`
merges page-specific template vars with common layout vars from `getLayoutParams()` (auth state, current user +
avatar, memory/time usage, unread messages, etc.) before handing off to Twig.

Route groups beyond the obvious CRUD-ish ones:
- `/dialog/(\d+)/messages` (GET) — JSON endpoint returning a freshly rendered message-list HTML fragment + counts,
  used by both `chat.js`'s AJAX-send flow and its polling loop (see Frontend section below).
- `/profile`, `/profile/personal`, `/profile/password`, `/profile/avatar`, `/profile/resend-verification` — the
  self-service account settings area (`App::profile*` methods), all POST-and-redirect-back-to-`/profile` with a
  one-shot flash message stored in `$_SESSION['profileMessage']`.

### Database layer (`Core\Database\DataBase`)
Singleton facade (`DataBase::getInstance($driver)`) wrapping a `DatabaseDriverInterface` implementation, selected in
the constructor via a `match` on driver name (currently only `'mysql'` → `Core\Database\Drivers\MySqlDriver`; the
`match` structure is meant for adding other drivers under `core/lib/Core/Database/Drivers/`). The facade exposes
`query`, `add`, `update`, `delete`, `get`, `getList`, `getCount`, and transaction methods
(`startTransaction`/`commitTransaction`/`rollbackTransaction`), all delegating to the underlying driver. Table names
throughout the codebase are prefixed with `DB_TABLE_PREFIX` (e.g. `Core\Models\MQ::TABLE = DB_TABLE_PREFIX . 'threads'`).

`MySqlDriver::update()`/`delete()` always return `false` in practice (they infer success from `fetchAll()` on a
non-SELECT statement, which is always empty) — don't branch on `DataBase::update()`'s return value; a thrown
`DatabaseException` is the only reliable failure signal. Known dormant bug: `User::update()`'s cache invalidation
key doesn't match the keys `User::getAllUserData()` actually caches under (it's missing the `onlySecureData` flag
component), so cached user data is never properly invalidated on write — currently harmless because `USE_CACHE` is
off by default, but will surface stale data (stale password/email/etc. via `getLogin()`/`getEmail()`/`getAllUserData()`)
if caching is ever turned on. Fix by aligning the cache key format in `update()` with `getAllUserData()`'s.

### Models (`core/lib/Core/Models/`)
Plain classes (not an ORM) wrapping `DataBase` calls per feature area:
- `User`/`UserModel`/`UserMeta` — auth, profile, presence. `isOnline($id)`/`getOnlineCount()` compute presence from
  `last_active` + `USER_ONLINE_TIME`; `last_active` is only ever refreshed as a side effect of `isAuthorized()`
  (both its cookie- and session-based branches). `changePassword()`/`changeEmail()` are the self-service versions
  used by `/profile` (as opposed to `resetPassword()`, which force-generates and emails a random password). **Gotcha:**
  `changePassword()` must refresh `$_SESSION['password']` after a successful change, or the very next request's
  `isAuthorized()` session-consistency check (which compares a double-hash of the current DB password against the
  hash captured at login) will silently log the user out — this is exactly the kind of bug that looks like "wrong
  current password" on the *next* attempt when it isn't.
- `Dialog` — private messages; `getLastMessage($dialogId)` backs the inbox-style previews in the dialogs list.
- `Posts`, `Roles`, `File`, `UTM` (marketing tag capture, runs on every full-load request), and `MQ`/`MQResponse` (a
  DB-backed message-queue/task-runner used by the admin "Менеджер очереди" panel and by `MQTasks.class.php`).
- `Core\DataObjects\AbstractModel`/`AbstractCollection`/`ModelCollection` provide a lightweight base for
  model/collection behavior used by newer models.

### Frontend (`templates/`, `assets/`)
Server-rendered Twig, one shared layout, no client-side framework or build step.
- `templates/layout.twig` is the page shell: a centered "card" (`.shell`) with a gradient header/footer and an icon
  nav, floating on a plain page background — not a full-bleed app chrome. Every page renders inside `.shell-content`.
- `templates/_icons.twig` holds every icon as a Twig macro emitting inline SVG (no icon font, no CDN). **Import it
  per-template** — `{% import '_icons.twig' as icons %}` at the top of *each* template that uses `icons.xxx()`, since
  Twig imports are not inherited from the parent template through `{% extends %}`.
- `assets/css/style.css` is a single hand-written stylesheet using CSS custom properties (`:root` design tokens:
  colors, radii, shadows, the `--gradient-brand` used everywhere for primary accents). Reusable component classes:
  `.page-head` (page title + icon), `.section`/`.section-title` (content grouping — deliberately *not* its own
  bordered box, to avoid a "card inside a card" look now that `.shell` already frames the page), `.info-list` /
  `.meta-grid` (key/value display), `.status` + `.avatar-wrap`/`.avatar-status-dot` (online/offline presence dot
  overlaid on an avatar), `.pill`/`.nav-badge` (counts), `.dialog-list`/`.dialog-row` (messenger-style inbox rows).
  When adding a new panel-like UI element, avoid giving it its own `border`/`box-shadow` if it already lives inside
  `.shell-content` — that's the recurring mistake that produces the nested-window look.
- Dialogs/chat (`dialog.twig`, `dialogs.twig`, `templates/_messageList.twig`, `assets/js/chat.js`): the message-list
  markup (grouping consecutive messages from the same sender, day separators, per-type rendering for
  text/image/file) lives in exactly one place, `_messageList.twig`, which is rendered both for the normal full-page
  load and, standalone, by `App::outputDialogJson()` for AJAX responses — keep it that way rather than duplicating
  render logic in JS. `chat.js` handles AJAX send (`FormData`, no full-page reload), textarea auto-resize,
  Enter-to-send (Shift+Enter for newline), and polls `/dialog/(\d+)/messages` every few seconds for new messages,
  only re-scrolling to bottom if the user was already scrolled near the bottom.
- Avatars: `File`-backed images are optional per user — every place that renders an avatar (nav, dialogs list, chat
  header, profile) must handle the "no `image_id`" case by falling back to an initial-letter badge; see any existing
  `{% if x.avatar %}...{% else %}...{% endif %}` block for the pattern (and use `name|default(login)`, not
  `nameForDisplay`, for the initial — the latter is a formatted `"[id] (login) name"` string).

### Helpers (`core/lib/Core/Helpers/`)
Cross-cutting utilities: `Cache` (file-backed, gated by `USE_CACHE`/`CACHE_DIR`/`CACHE_TTL`, initialized once in
bootstrap), `Log`, `Mail`, `Captcha`, `DDosProtection` (basic rate limiting per `USE_DDOS_PROTECTION`), `Registry`
(simple static key/value store used e.g. to stash the current route callback), `Sanitize`, `Files`, `Thumbs`, `Zip`,
`SystemFunctions`, `Pagination` (`execute()`/`getLimit()` compute the SQL `LIMIT` offset; `getPage()`/`getTotalPages()`
getters exist for rendering prev/next controls — call `execute()` before either getter).

### External services (`core/lib/Core/ExternalServices/`)
Thin clients for Telegram (`Telegram`, `Telegram2`, `TelegramSender`, `TelegramActions` — note there are parallel/
legacy variants, check which is actually wired up before extending), `ChatGPT`, `RemoteHosts`, and an HTTP
`Request`/`RequestOLD` pair (prefer `Request/` over the OLD variant for new code).

### Configuration constants
`core/config.php` defines all runtime configuration as global constants (`DB_HOST`, `CACHE_*`, `TELEGRAM_*`,
`USE_CAPTCHA`, `USE_DDOS_PROTECTION`, `CRYPTO_KEY`, etc.), each wrapped in `if (!defined(...))` so a `config.local.php`
at the project root can override any subset before `core/config.php` runs. `Core\SystemConfig::getValue($name)` is a
thin `constant()` wrapper used where a config value needs to be fetched dynamically by name rather than referenced
directly. Note: this repo currently has real-looking secrets (DB password, Telegram bot token, crypto key) committed
directly in `core/config.php` and `phinx.php` — don't add more secrets this way; if touching this area, prefer
environment-driven config and flag the existing hardcoded values rather than propagating the pattern.

### Testing
PHPUnit tests live in `tests/` (currently minimal — see `tests/UserTest.php`). CI (`.gitlab-ci.yml`) runs
`./vendor/bin/phpunit --testdox tests` on every push; a separate GitHub Actions workflow
(`.github/workflows/psalm.yml`) runs Psalm's security scan on `main` and PRs into it. There's no automated test
coverage for routes/templates — when changing `App.class.php` controllers or Twig templates, sanity-check with
`php -l` plus a throwaway Twig render (see Commands above) before considering the change done, and prefer verifying
session-dependent flows (login, password change, AJAX endpoints) against the live `dev.it-stories.ru` sync target
with `curl` and a cookie jar rather than guessing from code alone.
