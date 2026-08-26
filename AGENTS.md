# AGENTS.md

This file provides guidance to agents when working with code in this repository.

## The Starter and Its Projects

Every XM Media Craft site starts as a copy of the starter,
**https://github.com/xmmedia/starter_craft** (public, `master`), made with
`composer create-project xm/starter_craft … --remove-vcs`. This file is copied along too, so
it's in use on both sides — the starter is the one that still has `xm_starter_craft` in
`package.json` and unresolved `@todo-craft` markers.

The starter is a skeleton, never a deployed site: it's copied, the `@todo-craft` markers are
resolved, and the site is built out from there. So everything below describing content,
sections, blocks or templates is a *starting point* — in a project, read the project's own
files rather than assuming the starter's are still what's there. Deployment and provisioning
apply to the project only; nothing deploys from the starter, and its `.gitlab-ci.yml` only
ever runs the static stage.

**Porting changes.** The two repos share no history, so `git merge` / `rebase` / `pull`
against the starter are out. **`git cherry-pick` works** — it applies a commit's patch
against that commit's own parent, no shared ancestor needed, and only conflicts if both
sides edited the same lines. It works in reverse too (project → starter), minus anything
project-specific.

```sh
git remote add starter https://github.com/xmmedia/starter_craft.git  # once
git fetch starter
git log starter/master --oneline -20        # what's changed upstream
git cherry-pick <sha>                       # port one commit across (-n to stage only,
                                            #   for commits that also touch project values)
git diff starter/master -- vite.config.mjs  # or just compare one path
```

**`@todo-craft` markers** are how the skeleton flags what a new project has to decide —
across `.env.example*`, `.gitignore`, `.gitlab-ci.yml`, `.lando.yml`, `package.json`,
`vite.config.mjs`, `config/`, `public/css/` and `templates/`. List them with
`grep -rn '@todo-craft' --exclude-dir={node_modules,vendor,.git} .`
- Most mark a value to replace (site URL, ports, timezone, app ID, from-address, main branch
  name); some mark a decision (CSS to uncomment if the design needs it, favicons to
  regenerate, files to stop ignoring). Remove the marker once it's resolved — a project with
  none left is fully set up
- Any project-specific value added to the starter needs a marker on it

**Working in the starter**: some changes are worth porting into existing projects, others
only apply to new ones (setup scripts, scaffolding, defaults too disruptive to retrofit) —
don't assume either way, and ask when it's unclear. Keep changes generic, with anything
project-specific behind a marker. One commit is the unit of porting: keep unrelated changes in separate commits, and note
"new projects only" in the message where it applies — the message is the only record a
project sees.

**Starter-owned** (a change here often belongs on both sides): `bin/`, `modules/`,
`.gitlab-ci.yml`, `.lando.yml`, `lando_apache_vite.conf`, `vite.config.mjs`, `.yarnrc.yml`,
`eslint.config.mjs`, `stylelint.config.mjs`, `.twig_cs.php`, `php_cs.dist`, `rector.php`,
`phpstan.neon.dist`, `provision_site.sh`, `setup_server.sh`, `setup_gitlab_ci_vars.sh`, and
this file.

**Project-owned** (the starter's versions are only a starting point): `templates/`,
`public/css/`, `public/js/src/`, `public/images/`, `config/project/`, `config/custom.php`.
`config/general.php` and `config/app.php` are shared, with project values behind
`@todo-craft`.

## Project Configuration

Set per project and all marked `@todo-craft`. The values shown are the starter's own — in a
project, read them from the files listed:

| Setting | Starter value | Where it lives |
|---------|---------------|----------------|
| Project slug | `craftstarter` | `.lando.yml` (`name`, `proxy`) |
| Local URL | `https://craftstarter.lndo.site/` | `.lando.yml` `proxy`, `vite.config.mjs` `siteOrigin`, `config/vite.php` `devServerPublic`, `.env` `PRIMARY_SITE_URL` |
| PHPMyAdmin URL | `https://pma.craftstarter.lndo.site/` | `.lando.yml` `proxy` |
| Vite dev port | `9028` | `vite.config.mjs`, `lando_apache_vite.conf` (both `BalancerMember`s) |
| Preview port | `9528` | `vite.config.mjs`, `.lando.yml` (published by the `node` service) |

## System Requirements

- PHP 8.5+
- MySQL 8.4
- Node 24
- [Yarn v4](https://yarnpkg.com/en/docs/install)

## Development Commands

### Frontend (JavaScript/CSS)

Node/yarn run in the Lando `node` service — `lando yarn <command>` for everything,
`lando yarn install` to install — so the Node version matches `.nvmrc` rather than the
host's. Running them on the host still works; one `node_modules` is shared over the Lando
mount between host and container, which is why `.yarnrc.yml` widens
`supportedArchitectures` (darwin/linux × arm64/x64 × glibc/musl) — without it the
container dies on a missing `@rolldown/binding-linux-*`.

- **Dev server with HMR**: `lando vite` (runs `yarn install` then `yarn dev` in the
  container, so packages are always in sync before it starts). Assets are
  served **through the appserver** at `https://{local URL}/vite-dev/`, not from the Vite
  port directly — see **Vite through the appserver proxy** below. Run one dev server at a
  time
  - `vite.config.mjs` picks the certificate by checking for `/certs/cert.crt`: inside the
    container it uses the one Lando issues the service (`ssl: true`) — signed by the CA Lando
    installed on the host, with `localhost`/`127.0.0.1` in the SANs; on the host it falls back
    to `vite-plugin-mkcert`. mkcert can't work in the container — it needs root, and a CA
    generated there isn't trusted by the host browser, so the plugin is skipped when `inLando`
  - **On the host instead**: `yarn dev` works with no config change — Apache falls back to
    it (see the balancer below). Craft's URLs are identical either way
  - The Vite port is deliberately **not** published by the `node` service: the appserver
    reaches it over the internal network, and publishing it would shadow a host-run
    `yarn dev` on `127.0.0.1`, which is what makes the fallback fail
  - **Stop it**: `lando vite-stop` — killing `lando vite` on the host can leave the process
    holding the port inside the container
  - Toggle via `ENVIRONMENT` or `CRAFT_ENVIRONMENT` env vars (see `config/vite.php`)
- **Compile check**: `lando yarn build:check` — use this to verify JS/CSS compiles. Builds to
  `node_modules/.build-check` (gitignored, and ignored by the dev server's watcher), so
  it's safe to run while `lando vite` is running. A green build only proves it bundles —
  the browser is still the real check
- **Preview a production build**: `lando yarn preview` — static server over `public/build` at
  `https://localhost:9528/build/`, mirroring the production paths — `base` keys off
  `isPreview` as well as `command`, since preview otherwise runs as `serve` and would
  inherit the dev base. The `node` service publishes 9528 for it (its only published port)
- **Production build**: `lando yarn build` — production/deploy only. Never run it to verify a
  change: it empties and rewrites `public/build`, clobbering the manifest a running
  `lando vite` relies on
- **Linting**:
  - JS: `lando yarn lint:js` or `lando yarn lint:js:fix` (`eslint.config.mjs`)
  - CSS: `lando yarn lint:css` or `lando yarn lint:css:fix` (`stylelint.config.mjs`)
  - Twig: `lando composer lint:twig` (twigcs, config in `.twig_cs.php` — a custom
    ruleset, with a no-RegEngine variant for files where alignment spacing is intentional)
  - YAML (project config): `lando composer lint:yaml`
- **Security audits**:
  - Moderate: `yarn audit:moderate`
  - High: `yarn audit:high`
- **Upgrading packages**: `yarn up:bypass <package>` — runs `yarn up -R` with
  `YARN_NPM_MINIMAL_AGE_GATE=0`, bypassing the 7 day `npmMinimalAgeGate` in `.yarnrc.yml`
  that otherwise skips just-published versions. Match every package with `'**'`, not
  `'*'` — `*` doesn't cross the `/` in scoped names, so it misses `@tailwindcss/*` etc.

### PHP

- **Static analysis**: `lando composer static` (PHPStan, config in `phpstan.neon.dist`)
- **Code style**: `lando composer cs:fix` (PHP CS Fixer, config in `php_cs.dist`)
- **Automated refactoring**: `lando composer rector` (dry-run) / `lando composer rector:fix` (applies changes) — Rector, config in `rector.php`

### Full checks

- **Quick check** (no fixes): `bin/check` — `composer validate`, PHPStan, ESLint,
  Stylelint, YAML lint, Twig lint, then the security checks (`symfony security:check`,
  `composer audit`, `yarn audit:high`)
- **Full check** (runs Rector and PHP CS Fixer to fix code first, then `bin/check`): `bin/check_full`
  - Run `bin/check_full` before opening a PR
  - `bin/check` runs everything through Lando (`lando composer` / `lando yarn`), so it doesn't depend on the host's Node or PHP

### Craft CMS

- **Run console**: `./craft` (executable) or `lando craft` within Lando
- **Clear caches**: `lando craft clear-caches/all`
- **Apply migrations**: `lando craft up`
- **Craft MCP**: `.mcp.json` registers a `craft-cms` MCP server (runs
  `vendor/stimmt/craft-mcp/bin/mcp-server` through `lando ssh`). Server-side settings in
  `config/mcp.php` — off unless `MCP_ENABLED`, `tinker`/`run_query` disabled, localhost only

### Local Development (Lando)

- **Database**: accessible at `database:3306` (credentials in `.lando.yml`)
- **Xdebug**: `lando xdebug-on` / `lando xdebug-off`
- **Rebuild**: `lando rebuild` — needed after any change under `config:` or a service's
  `ports:`; `lando start` isn't enough. Rebuilding also recreates the appserver container,
  so run `lando start` afterwards or the app 404s
- **Node/Vite**: the `node` service (`node:24`, `ssl: true`; publishes only 9528 for
  `yarn preview`) — see **Frontend** above for `lando yarn` / `lando vite` / `lando vite-stop`

## Architecture Overview

### Technology Stack

**Backend:** Craft CMS 5, Twig, four custom Yii2 modules. Plugins the starter ships (a
project may have added more — check `composer.json`): Contact Form (+ Honeypot, +
Extensions), CKEditor, SEO (Ether), Field Manager (Verbb), Asset Usage (born05), oEmbed,
Vite (nystudio107), Craft MCP (stimmt).

**Frontend:** Vue 3, Vite, Tailwind CSS 4, ESLint + Stylelint. No PostCSS pipeline —
Tailwind 4's Lightning CSS handles nesting and prefixing.

**Browser support floor**:
- Set by Vite's default `build.target` (`baseline-widely-available`) and Tailwind 4's own
  hard-coded Lightning CSS targets. They currently agree: **Chrome 111+, Edge 111+,
  Firefox 114+, Safari/iOS 16.4+**
- **Neither reads browserslist** — a `.browserslistrc` here would be inert and would drift
  out of sync silently, so there deliberately isn't one
- Upgrading Vite or Tailwind can move the floor on its own — check their release notes.
  The current values live in `vite`'s `ESBUILD_BASELINE_WIDELY_AVAILABLE_TARGET` and
  `@tailwindcss/node`'s `targets`

### Project Structure

```
/config                 # Craft CMS configuration
  /project              # Project Config (YAML) - version controlled Craft settings
  /ckeditor             # CKEditor config (default.js)
  /htmlpurifier         # HTML Purifier config (Default.php)
  /rebrand              # Craft admin logo customizations
  app.php               # App/module registration
  general.php           # General Craft settings
  custom.php            # Custom settings (formNames, gaTrackingId)
  vite.php              # Vite integration config
  mcp.php               # Craft MCP plugin settings
  routes.php            # Custom URL routes

/modules                # Custom Yii2 modules (PSR-4: modules\)
  /contactformmodule    # Contact form customization + queued send job
  /xmmodule             # XM Media functionality + Twig extension
  /imagemodule          # Image rendering Twig extension
  /seomodule            # Sitemap filtering

/templates              # Twig templates
  _layout.twig          # Base layout
  _page.twig            # Page template wrapper
  _home.twig            # Home page template
  404.twig, error.twig  # Error templates
  /_emails              # Email templates (contact form notification)
  /_includes            # Reusable template partials
    _blocks.twig        # Matrix block dispatcher
    _meta.twig          # <meta>, Open Graph & Twitter tags
    _bg_style_block.twig # bg_style_block() macro — image-set() backgrounds
    _listing.twig       # Blog post listing grid
    _social_media.twig, _favicons.twig, _form_messages.twig
    /blocks             # Block-specific partials (accordion, banner, etc.)
  /blog                 # Blog templates
  /services             # Scaffolding — build out or delete (@todo-craft)

/public                 # Web root
  /build                # Compiled assets (generated by Vite)
  /assets               # Craft-managed assets (symlinked in prod)
  /js/src               # Source JavaScript
    public.js           # Main public entry point
    editor.js           # CKEditor customizations
    /common             # Shared JS (ajax_form.js, lib.js, menu.js)
  /css                  # Source CSS
    public.css          # Main entry, imports the partials
    editor.css          # CKEditor styles
    /partials           # Base/config/component styles
    /blocks             # Block-specific styles
  /images               # Static images

/bin                    # check / check_full scripts

/storage                # Craft runtime files (logs, cache, sessions)
```

### Custom Modules

The application bootstraps four custom Yii2 modules in `config/app.php`. After editing
one, `lando craft clear-caches/all` (and a PHP-FPM reload in production).

1. **ContactFormModule** (`modules/contactformmodule/ContactFormModule.php`):
   - Sets from address to default mailer from
   - Adds custom validation for name, email, and message fields
   - Modifies subject line format
   - Queues the emails rather than sending them inline — see **Queued contact form
     emails** below

2. **XmModule** (`modules/xmmodule/XmModule.php`):
   - Twig extension (`twigextensions/XmTwigExtension.php`):
     - functions — `blockWidth()`, `blockId()`, `menu()`, `submenu()`
     - filters — `heading_striptags`, `phone_strip`, `address_format`
   - Validates the shared `elementId` field on save — see **Block element IDs** below
   - `forceLowercaseFilenames()` — lowercases asset filenames and extensions on upload,
     rename, move and replace, while the asset *title* keeps its original casing (the cased
     name is carried between the two events it hooks)

3. **ImageModule** (`modules/imagemodule/ImageModule.php`):
   - Twig extension (`twigextensions/ImageTwigExtension.php`) — `image(asset, transform,
     attributes)` and `imageOrSvg()` functions for rendering images/SVGs

4. **SeoModule** (`modules/seomodule/SeoModule.php`):
   - Keeps entries flagged "Hide from Search Engines" out of the sitemap — see
     **Page SEO** below

### Frontend Build System

**Vite through the appserver proxy**:
- In dev, Apache proxies `/vite-dev/` to the node service (`lando_apache_vite.conf`,
  symlinked into `conf-enabled` by a `build_as_root` step), so dev assets are same-origin
  with the site. `base` in `vite.config.mjs` and `devServerPublic` in `config/vite.php`
  both have to match that path
- **Why**: SVG `<use href>` cannot cross origins — there is no CORS opt-in for it. That's
  what previously forced the svgxuse polyfill in dev; with the proxy the icon sprite
  resolves natively and the polyfill is gone. Scripts and CSS were never the problem
- The proxy conf must live at server level, not in a vhost: `ProxyPass` is inherited by
  vhosts, but **mod_rewrite directives are not** — a `RewriteRule … [P]` for the websocket
  silently never fires from `conf-enabled`. Use `upgrade=websocket` on `ProxyPass` instead
- `ProxyPreserveHost On` means Vite sees the site's host, so it must be in `allowedHosts`
  — Vite rejects HMR websocket upgrades from unknown hosts with a bare `400`, which looks
  like a proxy bug but isn't
- `server.hmr.clientPort` is 443 and the protocol `wss`, because the HMR socket rides the
  proxied path rather than the Vite port
- The proxy is a **balancer**: the node container is the primary, a host-run `yarn dev`
  (`host.docker.internal`) is a hot standby, so either works at the same URL. Editing a
  `BalancerMember` needs a full Apache restart — the values live in shared memory, and a
  graceful reload keeps the old ones without saying so
- **A `503` on any `/vite-dev/…` URL means the dev server isn't running** — Apache has
  nothing to proxy to. Start it with `lando vite`. (Before the proxy the same situation
  showed up as a connection refused.) Note `pgrep -f vite` in the container gives a false
  positive by matching its own `sh -c` wrapper; check for a listener on the port instead

**Vite Configuration** (`vite.config.mjs`):
- Two entry points (`rolldownOptions` — Vite 8/Rolldown): `public.js` imports `public.css`
  and `images/icons-public.svg` then calls `initMenu()`/`initAjaxForms()`; `editor.js` is
  the CKEditor bundle
- Builds to `public/build` in manifest mode (for the Craft Vite plugin), no inlined assets
- Vue 3 + `@vitejs/plugin-vue` are installed; the starter mounts nothing — components and a
  `createApp()` call in an entry file get added per project

**AJAX forms** (`public/js/src/common/ajax_form.js`):
- Progressively enhances forms, posting via `fetch()` and rendering the response into
  `.js-form-messages` (see `_includes/_form_messages.twig`)
- Reads the `action` attribute directly — Craft's `actionInput()` hidden field shadows
  `form.action`. Success/failure copy comes from `data-success-message` /
  `data-fail-message`. Field errors are inserted as text; only CMS-authored or
  Craft-verified messages are set as HTML

**Mobile menu**:
- A `<dialog>` in `_layout.twig`, opened/closed via invoker commands (`command`/`commandfor`) — no JS needed
- `public/js/src/common/menu.js` adds a fallback for browsers without invoker support, and closes the dialog on link clicks and at the `md` breakpoint

**CSS Processing**:
- Tailwind CSS 4 (via `@tailwindcss/vite` plugin)
- Source maps enabled in dev mode

### Deployment

**GitLab CI/CD** (`.gitlab-ci.yml`):
- CI narrows `supportedArchitectures` back to the runner's own platform
  (`yarn config set --json supportedArchitectures …` in `before_script`) so only its native
  binaries install. `--immutable` is unaffected — the lockfile records every platform variant
  regardless
- **Static stage**: security checks (Symfony, Composer, yarn) then linting — JS, CSS,
  Twig and project config YAML. PHPStan is *not* run in CI; it only runs via `bin/check`
- **Deploy stages**: Staging and Production, both `when: manual`, and only on the
  `master` branch (`@todo-craft` — update if the project's main branch differs)
- Deploy jobs rsync to a timestamped release, symlink the shared folders into it, flip
  `current`, then `craft up` + clear caches + reload PHP-FPM, keeping the 2 most recent
  releases. The job fails if the site doesn't return 200 afterwards. Read `.gitlab-ci.yml`
  for the exact steps

**Shared Directories** (persist across deploys):
- `storage/` - logs, cache, sessions, rebrand assets
- `public/assets/` - user-uploaded content

**Provisioning Scripts** — run once when a project's server is set up, never against the
starter (the starter's README has the runbook):
- `provision_site.sh` — orchestrates provisioning a new site on an InterWorx server
- `setup_gitlab_ci_vars.sh` — sets the GitLab CI/CD variables for one scope
- `setup_server.sh` — creates the release/shared structure; runs on the server

Never run these — they're interactive and make changes to servers, 1Password and GitLab.
`provision_site.sh` and `setup_gitlab_ci_vars.sh` share the same structure, helpers and
output style; keep them in sync when editing either one.

### Content Management

**Craft Project Config**:
- All schema/settings version-controlled in `config/project/*.yaml`
- Changes propagated via `./craft up` (applies project config)
- Includes: fields, entry types, sections, volumes, image transforms, sites/site groups,
  users, addresses and GraphQL schemas. The starter has **no category groups and no global
  sets** — content that would once have been a global is a single (see **Singles as globals**)
- Fields are shared and reused across entry types, with the handle overridden per field
  layout where a block needs a different name (e.g. the `menuLink` field is `mainMenuButton`
  on the Menu entry type). Check `config/project/entryTypes/*.yaml` for the effective handle
  rather than assuming the field handle

**CKEditor Fields**:
- Reuse the existing `textBlock` or `textBlockSimple` fields (`config/project/fields/`) instead of creating a new CKEditor field
  - `textBlock` — full toolbar (headings, alignment, images, embeds, etc.)
  - `textBlockSimple` — minimal toolbar (bold, italic, link, super/subscript only)
- Only create a new CKEditor field if neither existing config fits the use case

**Notable `general.php` settings** (things that change how templates behave):
- `preloadSingles()` — singles are preloaded so they act like the old globals
- `enableTwigSandbox()`, `enableGql(false)`, `sendPoweredByHeader(false)`
- `runQueueAutomatically()` is on only when `DEV_MODE` — prod/staging need the
  every-minute cron
- `transformSvgs(false)`, `upscaleImages(false)`, `maxUploadFileSize('50M')`

**Entry Types and Sections**:
- Organized in `config/project/sections/` and `config/project/entryTypes/`
- The starter ships with (a project will have added to this — check
  `config/project/sections/`, or `list_sections` over the Craft MCP):
  - `pages` — structure, entry type `pageBlocks`, `{parent.uri}/{slug}` → `_page.twig`
  - `homepage` — single, also `pageBlocks` → `_home.twig`
  - `blog` — channel, entry type `blog`, `blog/{slug}` → `blog/_entry.twig`
  - `siteInformation`, `socialMedia`, `menus` — URL-less singles (see below)
- Two bits of scaffolding reference things the starter's project config doesn't define, so
  in a project confirm they were built out before relying on them (@todo-craft — otherwise
  delete):
  - `templates/services/` — `services/index.twig` queries a `services` section
  - `blog/_category.twig` and the `entry.postCategories` loop in `blog/_entry.twig` — no
    category group and no `postCategories` field

**Singles as globals** — `preloadSingles()` makes these available in every template by
section handle, no query needed:
- `siteInformation` — `companyName`, address, phone numbers, email (`_layout.twig`,
  `_includes/_meta.twig`)
- `socialMedia` — Matrix of platform + profile URL (`_includes/_social_media.twig`)
- `menus` — `menuHeader` / `menuFooter` Matrixes plus `mainMenuButton` /
  `mainMenuButtonLabel`; the `menu()` and `submenu()` Twig functions render from these

**Blocks**:
- Pages are built from a `blocks` Matrix field; `templates/_includes/_blocks.twig` loops
  the entries and `{% switch block.type.handle %}` includes the matching
  `_includes/blocks/_*.twig` partial. Adding a block type means: entry type in project
  config, a partial, and a `case` in `_blocks.twig` — a missing case renders an HTML
  comment (plus a visible notice in devMode)
- `_blocks.twig` also handles layout wrapping: `wrappedBlocks` are grouped into a
  `.blocks-wrap` div, `sectionStart`/`sectionEnd` open and close a `<section>`, and
  `notWithinSection` blocks force the section closed first. Width within the wrap comes
  from `blockWidth(block)` (a 12-column `col-span-*` class)
- `patternLibrary` renders `_pattern_library.twig` — a living style reference (headings,
  buttons, forms, colour swatches, icons, etc.). Add new shared component styles there so
  they're visible in one place
- An entry with an empty `blocks` field renders `_page_placeholder.twig` ("Content coming
  soon"). Set `includePlaceholder = false` before including `_blocks.twig` to suppress it
- Background images on `hero`/`banner` come from the `bg_style_block()` macro in
  `_includes/_bg_style_block.twig` — it emits a `{% css %}` rule with an `image-set()`
  srcset keyed to a per-block unique class, falling back to a single URL for SVGs

**Block element IDs**:
- Nearly every block has the shared `elementId` field, used for `#anchor` links
- Never write the attribute by hand — use `blockId(block)`, which returns the escaped
  ` id="…"` (leading space included) or an empty string:
  `<div class="{{ classes }}"{{ blockId(block) }}>`. `_heading.twig` is the exception:
  it passes `block.elementId` into Craft's built-in `heading()` function, which drops a
  null attribute itself
- Form blocks fall back to a generated ID so the post-submit scroll target always exists:
  `{% set formId = block.elementId ?? ('form--' ~ block.id) %}`
- `XmModule::validateElementIds()` rejects values that aren't a valid HTML `id` at save
  time — stricter than the spec (must start with a letter, then only letters, numbers,
  hyphens, underscores) so the value always works in a URL fragment and a CSS selector
  unescaped. Applied to any entry whose layout has the field, so new block types are
  covered automatically

**Queued contact form emails**:
- Contact form emails go through the queue (`modules/contactformmodule/jobs/SendEmail.php`)
  so a mail transport outage retries instead of silently dropping the message. Without
  it the email is lost: `craft\contactform\Mailer::send()` ignores the mailer's return
  value and always reports success to the visitor
- `ContactFormModule` flags the request on the Contact Form plugin's `EVENT_BEFORE_SEND`,
  then intercepts `craft\mail\Mailer::EVENT_BEFORE_SEND` — pushing the job and setting
  `$e->isValid = false` to cancel the inline send. Hooking the Craft mailer (rather than
  the plugin's own send) means the message is already final, including the notification
  template rendered by the Contact Form Extensions plugin
- The job stores the message as plain values rather than a serialised Symfony email, with
  attachment contents inlined — the uploaded temp file is gone by the time it runs.
  Embedded parts are spotted by their `inline` disposition, not `hasContentId()`: Symfony
  doesn't assign the content ID until send time
- Retries every `TTR` (5 min) for `MAX_ATTEMPTS` (10) tries, then the job is marked failed
  and shows in Utilities → Queue Manager. The retry delay *is* the TTR — the queue only
  re-reserves a job once its reservation times out
- Depends on the queue actually running: `runQueueAutomatically` is off outside dev, so
  prod/staging need the every-minute cron. If it stops, nothing sends. Submissions
  themselves aren't lost either way — Contact Form Extensions saves them to the DB
  (`enableDatabase`) before the send is attempted

**Page SEO / hiding a page from search engines**:
- Page meta comes from the `seo` Matrix field, *not* the Ether SEO plugin's own fieldwwhat 
  type — so the plugin's per-entry noindex option (`SitemapService::core()`) is inert
  here. The plugin is only used for the sitemap, robots.txt and redirects
- The `seo` field is on the `pageBlocks` entry type only (Pages + Homepage); `_page.twig`
  guards with `entry.seo is defined`, so sections without it (Blog) get no meta block.
  `seoOptional` is the same Matrix without Page Title required, for entry types where it
  shouldn't be mandatory (unused in the starter)
- Two lightswitches feed the `robots` tag built in `_page.twig`: `seoNofollow`, and
  `seoNoindex`, which also drops the page from the sitemap via `SeoModule::filterSitemap()`.
  Don't reimplement that as a URL rule — the plugin registers `sitemap.xml` on
  `EVENT_REGISTER_SITE_URL_RULES` and will silently overwrite a config route under the
  same key
- Which sections appear in the sitemap lives in the `seo_sitemap` DB table, not project
  config — set per environment in Settings → SEO → Sitemap

## Code Style and Patterns

**Comments** (all languages):
- Don't add comments explaining a change or restating what the code does. Comments are for
  future development, not a place to reiterate conversation points — explain the reasoning
  in the response instead
- Comment only when the change isn't obvious from the code itself
- Keep comments clear and succinct

**PHP**:
- PSR-4 autoloading for modules
- PHPStan config available (`phpstan.neon.dist`)
- Declare strict types in all files

**JavaScript**:
- ESLint config in `eslint.config.mjs` (Flat Config format)
- Vue 3 style guide conventions

**CSS**:
- Tailwind utility-first approach
- Group related CSS properties together
- Stylelint with Tailwind config (`stylelint.config.mjs`)
- Prefer CSS animations over JavaScript-driven animations when possible
- Property order:
  1. Visually obstructive — `display: none`, `visibility: hidden`
  2. Visibility — `visibility`, `overflow`, `opacity`
  3. Positioning — `position`, `float`, `z-index`, `clear`
  4. Box Model — constraining props first (`display`, `max-width`), then structural (`top`, `width`, `margin`, `padding`); width before height / x before y
  5. Element-specific — `list-style`, `border-collapse`, `resize`
  6. Aesthetic display (greatest → least impact) — `filter`, `background`, `border`, `transform`, `box-shadow`
  7. Font — structural (`font-size`, `line-height`, `font-weight`, `font-family`), then aesthetic (`text-align`, `text-transform`), then visual (`color`, `text-shadow`, `letter-spacing`)
  8. UI-bound — `animation`, `transition`
  9. Browser-specific hacks
  10. `!important` declarations
  11. Deprecated / candidates for removal

**Twig**:
- Consistent naming: partials prefixed with `_`
- Layout inheritance via `_layout.twig`
- Page wrapper pattern via `_page.twig`

## Environment Configuration

- **Environment file**: `.env` (not in git). `.env.example` is the server template;
  `.env.example-local` the local/Lando one
- **Environment detection**:
  - `ENVIRONMENT` or `CRAFT_ENVIRONMENT` env var
  - Controls Vite dev server usage, error display, etc.
- **Multi-environment config**: Use `App::env()` in config files

## Important Notes

- **Craft License**: Stored in `config/license.key` (not in git on staging/prod)
- **Rebrand Assets**: Craft admin logo customizations in `config/rebrand/`, deployed to `storage/rebrand/`
- **Session Storage**: Custom path in `storage/sessions/` (configured in `config/app.php`)
- **SVG Processing**: SVGO config in `svgo.config.cjs`

## Updating PHP Version

When updating PHP (currently 8.5):
1. Update `composer.json` require version
2. Update in:
   - `.lando.yml` – `config.php` and `services.appserver.type` (if the Symfony recipe doesn't support the new version, override appserver with `type: php:X.X`)
   - `setup_server.sh` – 4 places
   - `.gitlab-ci.yml` – 3 places (default image, `SERVER_PHP_PATH`, `php-fpm` service name)
   - Add the related migration to `php_cs.dist`
3. Run `lando rebuild`
4. Update `composer.lock` via `lando composer update`
5. Update the version in this file (and `README.md`, in the starter)
