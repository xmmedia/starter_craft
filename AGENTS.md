# AGENTS.md

This file provides guidance to agents when working with code in this repository.

## Project Configuration

These values are project-specific and defined in `.lando.yml` and `vite.config.mjs`:

| Setting | Value | Files to update |
|---------|-------|-----------------|
| Project slug | `craftstarter` | `.lando.yml`, `vite.config.mjs`, this file |
| Vite dev port | `9028` @todo-craft | `vite.config.mjs`, `lando_apache_vite.conf` |
| Local URL | `https://craftstarter.lndo.site/` @todo-craft | Derived from lando config |
| PHPMyAdmin URL | `https://pma.craftstarter.lndo.site/` @todo-craft | Derived from lando config |

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

- **Dev server with HMR**: `lando vite` (runs `yarn dev` in the container). Assets are
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
- **Project config**: Stored in `config/project/` directory with YAML files
- **Craft MCP**: `.mcp.json` registers a `craft-cms` MCP server (runs
  `vendor/stimmt/craft-mcp/bin/mcp-server` through `lando ssh`). Server-side settings in
  `config/mcp.php` — off unless `MCP_ENABLED`, `tinker`/`run_query` disabled, localhost only

### Local Development (Lando)

- **URL**: See Project Configuration above
- **Database**: accessible at `database:3306` (credentials in `.lando.yml`)
- **Xdebug**: `lando xdebug-on` / `lando xdebug-off`
- **Rebuild**: `lando rebuild` — needed after any change under `config:` or a service's
  `ports:`; `lando start` isn't enough. Rebuilding also recreates the appserver container,
  so run `lando start` afterwards or the app 404s
- **Node/Vite**: the `node` service (`node:24`, `ssl: true`; publishes only 9528 for
  `yarn preview`) — see **Frontend** above for `lando yarn` / `lando vite` / `lando vite-stop`

## Architecture Overview

### Technology Stack

**Backend:**
- **Craft CMS 5** (CMS)
- Craft plugins: Contact Form, Contact Form Honeypot, Contact Form Extensions, CKEditor, SEO (Ether), Field Manager (Verbb), Asset Usage (born05), oEmbed, Vite integration, Craft MCP (stimmt)
- Custom Yii2 modules for extending functionality
- Twig templating engine

**Frontend:**
- **Vue 3** for interactive components
- **Vite** for bundling and dev server (replaces Webpack)
- **Tailwind CSS 4** for styling
- **PostCSS** with env-function and nesting plugins (autoprefixer via Tailwind)
- ESLint and Stylelint for code quality

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
    /blocks             # Block-specific partials (accordion, banner, etc.)
  /blog                 # Blog templates
  /services             # Services templates

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

The application bootstraps four custom Yii2 modules in `config/app.php`:

1. **ContactFormModule** (`modules/contactformmodule/ContactFormModule.php`):
   - Customizes Craft's Contact Form plugin behavior
   - Sets from address to default mailer from
   - Adds custom validation for name, email, and message fields
   - Modifies subject line format
   - Queues the emails rather than sending them inline — see **Queued contact form
     emails** below

2. **XmModule** (`modules/xmmodule/XmModule.php`):
   - Custom XM Media functionality
   - Twig extension (`twigextensions/XmTwigExtension.php`):
     - functions — `blockWidth()`, `blockId()`, `menu()`, `submenu()`
     - filters — `heading_striptags`, `phone_strip`, `address_format`
   - Validates the shared `elementId` field on save — see **Block element IDs** below

3. **ImageModule** (`modules/imagemodule/ImageModule.php`):
   - Twig extension (`twigextensions/ImageTwigExtension.php`) — `image(asset, transform,
     attributes)` and `imageOrSvg()` functions for rendering images/SVGs

4. **SeoModule** (`modules/seomodule/SeoModule.php`):
   - Keeps entries flagged "Hide from Search Engines" out of the sitemap — see
     **Hiding a page from search engines** below

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
- Two entry points: `public.js` and `editor.js` (via `rolldownOptions` — Vite 8/Rolldown)
- Output directory: `public/build`
- Dev server port and CORS origin: See Project Configuration
- HTTPS via the Lando service cert in the container, mkcert on the host
- Dev `base` is `/vite-dev/` — the path Apache proxies (production `base` is `/build/`)
- Manifest mode for Craft integration
- No inlined assets (all files separate)

**Entry point JS** (`public/js/src/public.js`):
- Imports `public.css` and `images/icons-public.svg`, then calls `initMenu()` and
  `initAjaxForms()` — no Vue app is mounted in the starter

**Vue Integration**:
- Vue 3 + `@vitejs/plugin-vue` are installed and the Composition API is available
- No components in the starter — add them (and a `createApp()` mount in an entry file)
  as a project needs them

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
- PostCSS with env-function and nesting plugins
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
- Deployment process:
  1. Security audits (Symfony, Composer, npm)
  2. Install dependencies and build assets
  3. rsync to timestamped release directory
  4. Copy rebrand assets and the shared `.env` into the release
  5. Symlink shared folders (storage, public/assets), then point `current` at the release
  6. Run Craft migrations (`craft up`) and clear caches
  7. Reload PHP-FPM
  8. Clean up old releases (keeps 2 most recent)
  9. Request the site and fail the job if it doesn't return 200

**Shared Directories** (persist across deploys):
- `storage/` - logs, cache, sessions, rebrand assets
- `public/assets/` - user-uploaded content

**Provisioning Scripts** (see README for the runbook):
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
- Includes: fields, sections, entry types, volumes, transforms, category groups, global sets

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
- Blog section with dedicated templates
- Services section with dedicated templates

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

**Hiding a page from search engines**:
- Page meta comes from the `seo` Matrix field, *not* the Ether SEO plugin's own field
  type — so the plugin's per-entry noindex option (`SitemapService::core()`) is inert
  here. The plugin is only used for the sitemap, robots.txt and redirects
- The `seoNoindex` lightswitch drives both the `robots` meta tag and sitemap exclusion,
  the latter via `SeoModule::filterSitemap()`. Don't reimplement that as a URL rule — the
  plugin registers `sitemap.xml` on `EVENT_REGISTER_SITE_URL_RULES` and will silently
  overwrite a config route under the same key
- Which sections appear in the sitemap lives in the `seo_sitemap` DB table, not project
  config — set per environment in Settings → SEO → Sitemap

## Development Workflow

1. **Making Template Changes**: Edit files in `templates/`, Craft auto-detects changes
2. **Making JS/CSS Changes**:
   - Run `lando vite` for the Vite dev server with HMR
   - Changes appear immediately in browser
   - Verify compilation with `lando yarn build:check` (safe alongside `lando vite`)
   - Production build via `lando yarn build` before deploy
3. **Adding Craft Fields/Sections**:
   - Make changes in Craft admin UI
   - Project Config auto-saves to `config/project/`
   - Commit YAML files to git
   - Other environments apply via `./craft up`
4. **PHP Module Changes**:
   - Edit files in `modules/`
   - Clear Craft caches: `./craft clear-caches/all`
   - May need to restart PHP-FPM in production

## Code Style and Patterns

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
- **Git Hooks**: None configured
- **Browserlist**: Specified in `.browserslistrc`

## Browser Automation

Use `agent-browser` for web automation. Run `agent-browser --help` for all commands.

Core workflow:
1. `agent-browser open <url>` - Navigate to page
2. `agent-browser snapshot -i` - Get interactive elements with refs (@e1, @e2)
3. `agent-browser click @e1` / `fill @e2 "text"` - Interact using refs
4. Re-snapshot after page changes

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
5. Update version in `README.md` and `AGENTS.md`
