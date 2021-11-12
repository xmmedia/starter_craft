# Craft CMS Starter

Used to create new projects using [Craft CMS](https://craftcms.com/) at [XM Media](https://www.xmmedia.com/).

## Setting Up a New Site

1. Create a new project:
    ```sh
    composer create-project xm/starter_craft project-name --stability=dev --no-install --remove-vcs
    ```
2. Update `composer.json`: `name`, `license` (likely `private`) and `description`
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
   9. Install craft: `./craft install/craft`
5. Remove or update the `LICENSE` file.
6. [Install Composer](https://getcomposer.org/download/) locally (if not installed globally).
7. Composer install & update (locally): `composer install && composer update`
8. Run `yarn && yarn upgrade` locally.
9. Upload `composer.lock` and `yarn.lock` and on the server, run `php composer.phar install` and `. ./node_setup.sh` again.
10. Find and make changes near `@todo-craft` comments throughout the site. All changed files will need to uploaded to the server.
11. Create new favicons: [realfavicongenerator.net](https://realfavicongenerator.net)
12. Add icon/logo as SVGs in `/storage/rebrand/icon/` and `/storage/rebrand/logo/` as `logo.svg`. (SVGs are best.) Only works with paid version of Craft.
13. Delete starter files: `README.md` (or update) and `TEMPLATES.md`.

**Dev site can be accessed at https://[domain]/**  
Craft admin is located at `/admin`

## System Requirements

  - PHP 8.0+
  - MySQL 5.7+
  - Node 14
  - [Yarn](https://yarnpkg.com/en/docs/install)

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
@todo update/review
  - Frontend – full list of dependencies can be found in [package.json](https://github.com/xmmedia/starter_symfony_4/blob/master/package.json)
    - [Vue](https://vuejs.org/) – frontend framework
      - [Vue Router](https://router.vuejs.org/) – routing package for frontend
      - [Vuex](https://vuex.vuejs.org/) – helps to manage state
      - [Vue Devtools](https://github.com/vuejs/vue-devtools) – makes debugging in the browser easier
      - [Vue Templates](https://vuejs.org/v2/guide/syntax.html) – the syntax for .vue files
      - [Vue Test Utils](https://vue-test-utils.vuejs.org/) – to help testing Vue components
    - [Vue CLI](https://cli.vuejs.org/) – to manage & run the frontend compilation & testing
    - [GraphQL](https://graphql.org/) – the communication (query) language for the API
      - [Apollo Client](https://www.apollographql.com/docs/react/) through [Vue Apollo](https://vue-apollo.netlify.com) – frontend GraphQL 
    - [SASS](https://sass-lang.com/) – CSS preprocessor (uses [node-sass](https://www.npmjs.com/package/node-sass))
    - [Webpack](https://webpack.js.org/) – compiles JS & CSS
      - [Babel](https://babeljs.io/) – transforms JS to work in all browsers
      - [Webpack Encore](https://symfony.com/doc/current/frontend.html) – connects the frontend and backend and makes Webpack configuration simpler
      - [PostCSS](https://github.com/postcss/postcss) – transforms CSS
      - [Autoprefixer](ub.com/postcss/autoprefixer) – for adding browser prefixes
      - [Purge CSS](https://github.com/FullHuman/purgecss) – removes unused CSS during the deployment process (not run in dev)
      - [SVGO](https://github.com/svg/svgo) – optimizes SVG files
      - [Bundle Analyzer](https://github.com/webpack-contrib/webpack-bundle-analyzer) – displays sizes/stats on the JS bundle size
    - [Tailwind](https://tailwindcss.com/) – utility first styling framework
    - [Jest](https://jestjs.io/) – JS unit testing
    - [Cypress](https://www.cypress.io/) – end-to-end (e2e) testing
    - [Lodash](https://lodash.com/) – helper functions for JS
    - [date-fns](https://date-fns.org/) – helper functions for Dates in JS
    - [PortalVue](https://github.com/LinusBorg/portal-vue) – helps to manage things like modals
    - [Vue-JS-Modal](http://vue-js-modal.yev.io/) – for modals 
    - [Faker.js](https://github.com/marak/Faker.js/) – for generating fake data in tests
    - [ESLint](https://eslint.org/) – checks JS for conventions & errors
    - [Stylelint](https://stylelint.io/) – checks CSS for conventions & errors
  - Backend – full list of dependencies can be found in [composer.json](https://github.com/xmmedia/starter_symfony_4/blob/master/composer.json)
    - [Symfony](https://symfony.com/doc/current/index.html#gsc.tab=0) – backend framework
    - [GraphQLBundle](https://github.com/overblog/GraphQLBundle) – provides GraphQL in PHP using [graphql-php](https://github.com/webonyx/graphql-php)
      - [GraphQiL](https://github.com/graphql/graphiql) is available at `/graphiql` (on dev only)
    - [Twig](https://twig.symfony.com/) – server side templating language (limited use)
    - [PhpUnit](https://phpunit.de/) – for running PHP tests
    - [PHP CS](https://cs.sensiolabs.org/) – PHP coding standards analyzer & fixer
    - [PHPStan](https://github.com/phpstan/phpstan) – static analysis of PHP
    - [Postmark](https://postmarkapp.com/) – for sending email, contains email templates (currently setup under XM Media's account)
    - [Cloudflare](https://www.cloudflare.com/) – DNS & CDN
  - [GitLab](https://gitlab.com/) – deployment
  - Dev Tools
    - [Vue Devtools](https://github.com/vuejs/vue-devtools)
    - [Apollo Devtools](https://github.com/apollographql/apollo-client-devtools)
