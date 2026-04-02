# Craft CMS Starter

Used to create new projects using [Craft CMS](https://craftcms.com/) at [XM Media](https://www.xmmedia.com/).

## Setting Up a New Site

1. Create a new project:
    ```sh
    composer create-project xm/starter_craft project-name --stability=dev --no-install --remove-vcs
    ```
2. Update `composer.json`: `name`, `license` (likely `proprietary`) and `description`
3. Update `package.json`: `name`, `version`, `git.url`, `license`, `private`, `script.dev-server`
4. Setup dev server:
   1. If using InterWorx, upload `setup_dev.sh` and run: `sh ./setup_dev.sh` 
   2. Upload the files (exclude files that are OS dependent like `node_modules` & `.env` or that are only for editing like `.idea` and `.git` and a lot of what's in `.gitignore`).
   3. [Install Composer](https://getcomposer.org/download/) (if not already installed)
   4. Install PHP packages/vendors: `php composer.phar install`
   5. Add `.env` (copy `.env.example` and update).
   6. Run `. ./node_setup.sh` (this will setup node & install the JS packages – requires yarn to be installed).
   7. Run `yarn dev` or `yarn build` (for production) to compile JS & CSS files.
   8. Give executable perms to bin dir: `chmod u+x craft`
   9. Ensure you have lando running.
   10. Within `lando ssh`, install craft: `./craft install/craft`
5. Remove or update the `LICENSE` file.
6. [Install Composer](https://getcomposer.org/download/) locally (if not installed globally).
7. Composer install & update (locally): `composer install && composer update`
8. Run `yarn && yarn upgrade` locally.
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

## System Requirements

  - PHP 8.5+
  - MySQL 8.0
  - Node 22
  - [Yarn v4](https://yarnpkg.com/en/docs/install)

## Commands

  - Production JS/CSS build: `yarn build`
  - Dev JS/CSS build: `yarn dev`
  - Dev JS/CSS watch: `yarn watch` (files will not be versioned)
  - Dev JS/CSS HMR server: `yarn dev-server` (see below)
  - JS Tests ([Jest](https://jestjs.io/)): `yarn test:unit`
  - E2E Tests ([Cypress](https://www.cypress.io/)): `yarn test:e2e`
  - Linting:
    - JS ([ESLint](https://eslint.org/)): `yarn lint:js` or `yarn lint:js:fix`
    - CSS: `yarn lint:css` or `yarn lint:css:fix`
  - PHP Tests ([PhpUnit](https://phpunit.de/)): 
    - `composer test`
    - no memory limit `php -d memory_limit=-1 bin/simple-phpunit`
    - with coverage (HTML) `composer test:coverage`
  - [PHP CS](https://cs.sensiolabs.org/): (must be installed first)
    - Dry run: `composer cs`
    - Fix: `composer cs:fix`
  - PHP Static Analysis ([PHPStan](https://github.com/phpstan/phpstan)): `composer static`

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
    - `setup_dev.sh` – 4 places
    - `setup_prod.sh` – 4 places
    - `.gitlab-ci.yml` – 3 places (default image, `SERVER_PHP_PATH`, and `php-fpm` service name)
    - `.php-cs-fixer.dist.php` – add the new version or update the `@PHP8#Migration` version to match the current version.
1. Run `lando rebuild` to rebuild the Lando container with the new PHP version.
1. Run `lando composer update` or `composer update` to update the PHP dependencies. If running locally without Lando, ensure your local PHP version matches the new version.
1. Update version in `README.md` and `CLAUDE.md`.

consider: https://plugins.craftcms.com/image-toolbox?craft4
