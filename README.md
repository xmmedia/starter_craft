# Craft CMS Starter

Used to create new projects using [Craft CMS](https://craftcms.com/) at [XM Media](https://www.xmmedia.com/).

## Setting Up a New Site

1. Create a new project:
    ```sh
    composer create-project xm/starter_craft project-name --stability=dev --no-install --remove-vcs
    ```
2. Update `composer.json`: `name`, `license` (likely `proprietary`) and `description`
3. Update `package.json`: `name`, `version`, `git.url`, `license`, `private`
4. Setup dev server:
   1. If using InterWorx, run `./provision_site.sh` locally — it provisions the whole site (see [Provisioning a Site](#provisioning-a-site) below) and runs `setup_server.sh` for you. To do it by hand instead, upload `setup_server.sh` to the domain dir and run: `sh ./setup_server.sh`
   2. Upload the files (exclude files that are OS dependent like `node_modules` & `.env` or that are only for editing like `.idea` and `.git` and a lot of what's in `.gitignore`).
   3. [Install Composer](https://getcomposer.org/download/) (if not already installed)
   4. Install PHP packages/vendors: `php composer.phar install`
   5. Add `.env` (copy `.env.example` and update).
   6. Run `. ./node_setup.sh` (this will setup node & install the JS packages – requires yarn to be installed).
   7. Run `yarn build` (for production) to compile JS & CSS files.
   8. Give executable perms to bin dir: `chmod u+x craft`
   9. Ensure you have lando running.
   10. Within `lando ssh`, install craft: `./craft install/craft`
5. Remove or update the `LICENSE` file.
6. [Install Composer](https://getcomposer.org/download/) locally (if not installed globally).
7. Composer install & update (locally): `composer install && composer update`
8. Run `lando yarn && lando yarn up -R '**'` locally.
9. Upload `composer.lock` and `yarn.lock` and on the server, run `php composer.phar install` and `. ./node_setup.sh` again.
10. Find and make changes near `@todo-craft` comments throughout the site. All changed files will need to uploaded to the server.
11. Create new favicons: [realfavicongenerator.net](https://realfavicongenerator.net)
12. Set the email Subject Text for contact form submissions in Settings > Contact Form (under Plugins).
13. Delete starter files: `README.md` (or update) and `TEMPLATES.md`.
14. Update site name:
    - In Settings -> General
    - In Settings -> Sites
    - Globals -> Site Information

**Dev site can be accessed at https://[domain]/**  
Craft admin is located at `/admin`

## Provisioning a Site

For sites on an InterWorx server, `./provision_site.sh` (run locally) does the whole setup:
1Password items, the SiteWorx account, the database, `setup_server.sh`, the PHP restart
sudoers entry, the Craft queue cron job and the GitLab CI/CD variables.

  - Requires an ssh Host with passwordless sudo, [`op`](https://developer.1password.com/docs/cli/)
    signed in, [`glab`](https://gitlab.com/gitlab-org/cli) authenticated and `jq` installed.
  - It asks whether you're provisioning staging or production, then prompts for everything
    else and shows a summary before making any changes.
  - The remaining manual steps (DNS record, SSL certificate, CI public key, `shared/.env`,
    first deploy, Craft database) are printed at the end. The DNS record doesn't need to
    exist beforehand — it's only needed before generating the SSL certificate.

Related scripts:

  - `setup_server.sh` – runs on the server as the site user, in the domain dir; creates the
    `releases`/`shared` structure and symlinks. Normally run by `provision_site.sh`.
  - `setup_gitlab_ci_vars.sh <group/project>` – sets the GitLab CI/CD variables for one
    scope (staging or production). Can be run on its own; see `--help`.

## System Requirements

  - PHP 8.5+
  - MySQL 8.4
  - Node 24
  - [Yarn v4](https://yarnpkg.com/en/docs/install)

## Commands

Yarn commands run inside the Lando `node` container via `lando yarn <command>`, so the
Node version matches [`.nvmrc`](.nvmrc) rather than whatever the host happens to have.
Running them on the host with `yarn <command>` still works.

  - Install JS packages: `lando yarn install`
  - Run all checks & fixes: `bin/check_full`
    - Runs Rector & PHP CS Fixer (applying fixes), then `bin/check`; run before pushing
  - Check all code (no fixes): `bin/check`
    - Runs linting (JS, CSS, YAML, Twig), PHP static analysis & security audits
  - Dev JS/CSS server with HMR: `lando vite` (runs `yarn dev` in the `node` container) or `yarn dev` on the host
    - Either way it's served at https://localhost:9028/ over HTTPS — in the container with the certificate Lando issues the service, on the host with [mkcert](https://github.com/liuweiGL/vite-plugin-mkcert). Run one or the other, not both
    - Stop a server left running in the container: `lando vite-stop`
    - Docker publishes the port, so while the app is up the host-side server is only reachable over IPv6 `localhost` (`::1`) — fine for browsers, but IPv4-only clients hit Docker instead
  - Compile check: `lando yarn build:check`
    - Builds to `node_modules/.build-check`, so it's safe to run while `lando vite` is running
  - Production JS/CSS build: `lando yarn build`
    - Don't use this to verify a change — it rewrites `public/build`, clobbering the manifest a running `lando vite` relies on
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
    - [PostCSS](https://github.com/postcss/postcss) – transforms CSS
      - [postcss-env-function](https://github.com/csstools/postcss-plugins/tree/main/plugins/postcss-env-function) – environment variable support in CSS
      - [postcss-nesting](https://github.com/csstools/postcss-plugins/tree/main/plugins/postcss-nesting) – CSS nesting support
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
    - [Craft Vite](https://nystudio107.com/docs/vite/) – Vite integration for Craft CMS
    - [PHPStan](https://github.com/phpstan/phpstan) – static analysis of PHP
  - [GitLab](https://gitlab.com/) – CI/CD and deployment
  - Dev Tools
    - [Vue Devtools](https://github.com/vuejs/vue-devtools)

## Updating PHP version

1. Change version in `composer.json`.
1. Update the PHP version in the following files:
    - `.lando.yml` – `config.php` and `services.appserver.type` (if the Symfony recipe doesn't support the new version, you must override the appserver service with `type: php:X.X`)
    - `setup_server.sh` – 4 places
    - `.gitlab-ci.yml` – 3 places (default image, `SERVER_PHP_PATH`, and `php-fpm` service name)
    - `php_cs.dist` – add the new version or update the `@PHP8#Migration` version to match the current version.
1. Run `lando rebuild` to rebuild the Lando container with the new PHP version.
1. Run `lando composer update` or `composer update` to update the PHP dependencies. If running locally without Lando, ensure your local PHP version matches the new version.
1. Update version in `README.md` and `AGENTS.md`.

consider: https://plugins.craftcms.com/image-toolbox?craft4
