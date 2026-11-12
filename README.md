# Craft CMS Starter

Used to create new projects using [Craft CMS](https://craftcms.com/) at [XM Media](https://www.xmmedia.com/).

## Setting Up a New Site

### Locally

1. Create a new project ([install Composer](https://getcomposer.org/download/) on the host first, if it isn't already):
    ```sh
    composer create-project xm/starter_craft project-name --stability=dev --no-install --remove-vcs
    ```
2. Update `composer.json`: `name`, `license` (likely `proprietary`) and `description`
3. Update `package.json`: `name`, `version`, `repository.url`, `license`, and uncomment `private`
4. Remove or update the `LICENSE` file.
5. Find and make changes near the `@todo-craft` comments throughout the site, removing each once it's resolved:
    ```sh
    grep -rn '@todo-craft' --exclude-dir={node_modules,vendor,.git} .
    ```
6. Copy `.env.example-local` to `.env` and update it. DDEV supplies the database settings
   and `PRIMARY_SITE_URL`, so they aren't in it.
7. Start DDEV: `ddev start` (if Lando is still running, see [Running alongside Lando](#running-alongside-lando))
8. Install & update PHP packages: `ddev composer install && ddev composer update`
9. Install & update JS packages: `ddev yarn && ddev yarn up -R '**'`
10. Install Craft: `ddev craft install/craft` – use `admin@xmmedia.com` for the admin email, or change
    `--user` in `.mcp.json` to match, otherwise the Craft MCP server won't start
11. Create new favicons: [realfavicongenerator.net](https://realfavicongenerator.net)
12. Delete or update the starter's `README.md`.

The local site is at `https://<name>.ddev.site/`, from `name` in `.ddev/config.yaml`
(`https://craftstarter.ddev.site/` in the starter); `ddev describe` lists it and phpMyAdmin.
Craft admin is located at `/admin`.

### Running alongside Lando

While Lando sites are running, Lando's proxy holds ports 80 & 443. Put DDEV's router on other
ports with a git-ignored `.ddev/config.local.yaml`, then `ddev restart`:

```yaml
#ddev-silent-no-warn
router_http_port: "8080"
router_https_port: "8443"
```

The site is then at `https://<name>.ddev.site:8443/`. Delete the file once Lando is no longer
running. To move an existing project from Lando, use the `/xm:lando-to-ddev` Claude skill. Run it after
pulling a project's switch to DDEV too: it moves your local databases from Lando into DDEV.

### In the Craft Admin

These are saved to project config, so make them locally and commit the changes.

1. Set the email Subject Text for contact form submissions in Settings > Plugins > Contact Form.
2. Update the site name:
    - In Settings > General
    - In Settings > Sites
    - In Entries > Site Information (content, so this one isn't in project config)

### Server

1. If using InterWorx, run `bin/provision_site` locally — it provisions the whole site (see
   [Provisioning a Site](#provisioning-a-site) below) and prints the remaining manual steps.
   To do it by hand instead:
    1. Upload `bin/setup_server.sh` to the domain dir and, as the site user, run: `bash ./setup_server.sh`
    2. Complete `shared/.env` (start from `.env.example`).
    3. Set the GitLab CI/CD variables: `bin/setup_gitlab_ci_vars <group/project>`
    4. Add the CI public key to the site user's `~/.ssh/authorized_keys`.
    5. Add the every-minute Craft queue cron job — the queue doesn't run automatically outside dev.
2. Deploy from GitLab (the Staging and Production jobs are manual, on the main branch).
3. Install Craft (`php craft install/craft` in the `current` dir) or import the local database.

The site can be accessed at `https://[domain]/`.

## Provisioning a Site

For sites on an InterWorx server, `bin/provision_site` (run locally) does the whole setup:
1Password items, the SiteWorx account, the database, `setup_server.sh`, `shared/.env`, the
Craft queue cron job, the GitLab CI/CD variables and the CI public key in the site user's
`~/.ssh/authorized_keys`.

  - Requires an ssh Host with passwordless sudo, [`op`](https://developer.1password.com/docs/cli/)
    signed in, [`glab`](https://gitlab.com/gitlab-org/cli) authenticated and `jq` installed.
  - It asks whether you're provisioning staging or production, then prompts for everything
    else and shows a summary before making any changes.
  - The remaining manual steps (DNS record, SSL certificate, completing `shared/.env`, first
    deploy, Craft database) are printed at the end. The DNS record doesn't need to
    exist beforehand — it's only needed before generating the SSL certificate.

Related scripts:

  - `bin/setup_server.sh` – runs on the server as the site user, in the domain dir; creates the
    `releases`/`shared` structure and symlinks. Normally run by `bin/provision_site`.
  - `bin/setup_gitlab_ci_vars <group/project>` – sets the GitLab CI/CD variables for one
    scope (staging or production). Can be run on its own; see `--help`.

## System Requirements

  - PHP 8.5+
  - MySQL 8.4
  - Node 26
  - [Yarn v4](https://yarnpkg.com/en/docs/install)

## Commands

Everything runs in the DDEV web container — `ddev yarn`, `ddev composer`, `ddev craft` — so
PHP and Node (from [`.nvmrc`](.nvmrc)) match the project rather than whatever the host has.

  - Install JS packages: `ddev yarn install`
  - Run all checks & fixes: `bin/check_full`
    - Runs Composer Normalize, Rector & PHP CS Fixer (applying fixes), then `bin/check`; run before pushing
  - Check all code (no fixes): `bin/check`
    - Runs `composer validate`, `composer normalize` (dry run), linting (JS, CSS, YAML, Twig), PHP static analysis & security audits
  - Dev JS/CSS server with HMR: `ddev yarn dev` (stop with Ctrl-C)
    - Assets are proxied through Apache at `https://[domain]/vite-dev/` so they're same-origin with the site — that's what lets the SVG icon sprite work without a polyfill (`<use href>` can't cross origins). See `.ddev/web-build/apache-vite.conf`
    - A `503` on a `/vite-dev/…` asset means the dev server isn't running — start it with `ddev yarn dev`
    - Stop one left running: `ddev exec pkill -f '[v]ite/bin/vite.js'`
  - Compile check: `ddev yarn build:check`
    - Builds to `node_modules/.build-check`, so it's safe to run while the dev server is running
  - Production JS/CSS build: `ddev yarn build`
    - Don't use this to verify a change — it rewrites `public/build`, clobbering the manifest a running dev server relies on
  - Preview a production build: `ddev yarn preview`
    - Serves `public/build` at `https://[domain]:4173/build/`, mirroring the production paths
  - Linting:
    - JS ([ESLint](https://eslint.org/)): `ddev yarn lint:js` or `ddev yarn lint:js:fix`
    - CSS ([Stylelint](https://stylelint.io/)): `ddev yarn lint:css` or `ddev yarn lint:css:fix`
    - Twig ([twigcs](https://github.com/friendsoftwig/twigcs)): `ddev composer lint:twig`
    - YAML: `ddev composer lint:yaml`
  - [PHP CS Fixer](https://cs.symfony.com/): `ddev composer cs:fix`
  - [Rector](https://getrector.com/): `ddev composer rector` (dry run) or `ddev composer rector:fix`
  - PHP Static Analysis ([PHPStan](https://github.com/phpstan/phpstan)): `ddev composer static`
  - Security audits: `ddev yarn audit:moderate` or `ddev yarn audit:high`
  - Upgrade a JS package, ignoring the age gate: `ddev yarn up:bypass <package>`
  - Import a database: `ddev import-db --file=<file.sql.gz>` (replaces the database)
  - Xdebug: `ddev xdebug on` / `ddev xdebug off` (off by default)

  There are no tests in the starter — add them (and their scripts) as a project needs them.

## Incorporated Libraries & Tools

  - Frontend – full list of dependencies can be found in [package.json](https://github.com/xmmedia/starter_craft/blob/master/package.json)
    - [Vue 3](https://vuejs.org/) – frontend framework
    - [Vite](https://vitejs.dev/) – frontend build tool and dev server with HMR
    - [Tailwind CSS 4](https://tailwindcss.com/) – utility-first styling framework
      - [@tailwindcss/typography](https://tailwindcss.com/docs/typography-plugin) – prose styling plugin
    - [ESLint](https://eslint.org/) – checks JS for conventions & errors
    - [Stylelint](https://stylelint.io/) – checks CSS for conventions & errors
    - [SVGO](https://github.com/svg/svgo) – optimizes SVG files
  - Backend – full list of dependencies can be found in [composer.json](https://github.com/xmmedia/starter_craft/blob/master/composer.json)
    - [Craft CMS 5](https://craftcms.com/) – CMS framework
    - [Twig](https://twig.symfony.com/) – server-side templating language
    - [CKEditor](https://github.com/craftcms/ckeditor) – rich text editor plugin for Craft
    - [Contact Form](https://github.com/craftcms/contact-form) – contact form plugin for Craft
    - [Contact Form Honeypot](https://github.com/craftcms/contact-form-honeypot) – spam protection for contact forms
    - [Contact Form Extensions](https://github.com/hybridinteractive/craft-contact-form-extensions) – additional contact form features
    - [SEO (Ether)](https://github.com/ethercreative/seo) – SEO plugin for Craft
    - [Field Manager (Verbb)](https://verbb.io/craft-plugins/field-manager) – field management plugin for Craft
    - [oEmbed](https://github.com/wrav/oembed) – oEmbed support for Craft
    - [Asset Usage](https://github.com/born05/craft-assetusage) – shows where assets are used
    - [Craft Vite](https://nystudio107.com/docs/vite/) – Vite integration for Craft CMS
    - Craft MCP – Craft's built-in MCP server, registered for agents in `.mcp.json`
  - [GitLab](https://gitlab.com/) – CI/CD and deployment
  - Dev Tools
    - [DDEV](https://ddev.com/) – Docker-based local dev environment
    - [PHPStan](https://github.com/phpstan/phpstan) – static analysis of PHP
    - [Vue Devtools](https://github.com/vuejs/vue-devtools)

## Updating PHP version

1. Change version in `composer.json`.
1. Update the PHP version in the following files:
    - `.ddev/config.yaml` – `php_version`
    - `bin/setup_server.sh` – 4 places
    - `.gitlab-ci.yml` – 2 places (default image and `SERVER_PHP_PATH`)
    - `php_cs.dist` – update the `@PHP8x5Migration` rule to match the new version.
1. Run `ddev restart` to rebuild the web container with the new PHP version.
1. Run `ddev composer update` or `composer update` to update the PHP dependencies. If running locally without DDEV, ensure your local PHP version matches the new version.
1. Update version in `README.md` and `AGENTS.md`.
