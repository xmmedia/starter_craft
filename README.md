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
6. Copy `.env.example-local` to `.env` and update it.
7. Start Lando: `lando start`
8. Install & update PHP packages: `lando composer install && lando composer update`
9. Install & update JS packages: `lando yarn && lando yarn up -R '**'`
10. Install Craft: `lando craft install/craft` – use `admin@xmmedia.com` for the admin email, or change
    `--user` in `.mcp.json` to match, otherwise the Craft MCP server won't start
11. Create new favicons: [realfavicongenerator.net](https://realfavicongenerator.net)
12. Delete or update the starter's `README.md`.

The local site is at the URL set in `.lando.yml` (`https://craftstarter.lndo.site/` in the starter).
Craft admin is located at `/admin`.

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

Yarn commands run inside the Lando `node` container via `lando yarn <command>`, so the
Node version matches [`.nvmrc`](.nvmrc) rather than whatever the host happens to have.
Running them on the host with `yarn <command>` still works.

  - Install JS packages: `lando yarn install`
  - Run all checks & fixes: `bin/check_full`
    - Runs Composer Normalize, Rector & PHP CS Fixer (applying fixes), then `bin/check`; run before pushing
  - Check all code (no fixes): `bin/check`
    - Runs `composer validate`, `composer normalize` (dry run), linting (JS, CSS, YAML, Twig), PHP static analysis & security audits
  - Dev JS/CSS server with HMR: `lando vite` (runs `yarn install`, then `yarn dev`, in the `node` container)
    - Assets are proxied through the appserver at `https://[domain]/vite-dev/` so they're same-origin with the site — that's what lets the SVG icon sprite work without a polyfill (`<use href>` can't cross origins). See `lando_apache_vite.conf`
    - Stop a server left running in the container: `lando vite-stop`
    - A `503` on a `/vite-dev/…` asset means the dev server isn't running — start it with `lando vite`
    - To run it on the host instead, just use `yarn dev` — Apache falls back to a host-run dev server, so the URLs and behaviour are the same
  - Compile check: `lando yarn build:check`
    - Builds to `node_modules/.build-check`, so it's safe to run while `lando vite` is running
  - Production JS/CSS build: `lando yarn build`
    - Don't use this to verify a change — it rewrites `public/build`, clobbering the manifest a running `lando vite` relies on
  - Preview a production build: `lando yarn preview`
    - Serves `public/build` at `https://localhost:9528/build/`, mirroring the production paths (9528 is the only port the `node` service publishes)
  - Linting:
    - JS ([ESLint](https://eslint.org/)): `lando yarn lint:js` or `lando yarn lint:js:fix`
    - CSS ([Stylelint](https://stylelint.io/)): `lando yarn lint:css` or `lando yarn lint:css:fix`
    - Twig ([twigcs](https://github.com/friendsoftwig/twigcs)): `lando composer lint:twig`
    - YAML: `lando composer lint:yaml`
  - [PHP CS Fixer](https://cs.symfony.com/): `lando composer cs:fix`
  - [Rector](https://getrector.com/): `lando composer rector` (dry run) or `lando composer rector:fix`
  - PHP Static Analysis ([PHPStan](https://github.com/phpstan/phpstan)): `lando composer static`
  - Security audits: `lando yarn audit:moderate` or `lando yarn audit:high`
  - Upgrade a JS package, ignoring the age gate: `lando yarn up:bypass <package>`

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
    - [PHPStan](https://github.com/phpstan/phpstan) – static analysis of PHP
    - [Vue Devtools](https://github.com/vuejs/vue-devtools)

## Updating PHP version

1. Change version in `composer.json`.
1. Update the PHP version in the following files:
    - `.lando.yml` – `config.php` and `services.appserver.type` (if the Symfony recipe doesn't support the new version, you must override the appserver service with `type: php:X.X`)
    - `bin/setup_server.sh` – 4 places
    - `.gitlab-ci.yml` – 2 places (default image and `SERVER_PHP_PATH`)
    - `php_cs.dist` – update the `@PHP8x5Migration` rule to match the new version.
1. Run `lando rebuild` to rebuild the Lando container with the new PHP version.
1. Run `lando composer update` or `composer update` to update the PHP dependencies. If running locally without Lando, ensure your local PHP version matches the new version.
1. Update version in `README.md` and `AGENTS.md`.
