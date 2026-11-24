# Upgrading a project to the starter

What a project built from an older starter has to do, & the traps it hits, when it catches up with a starter change.
`/xm:sync-starter` reads the entries after the project's baseline (`composer.json` → `extra.xm-starter.commit`),
`/xm:update-packages` reads it before a major package upgrade, & `/xm:new-project` deletes this file. Newest first.

Add an entry when a project has to do something the starter's diff & the package's own upgrade guide don't make
obvious, or you find a trap while upgrading a site that the next one will hit too. One line each: the trap & its fix.
Rules that always apply go in `/xm:frontend-guidelines` & `/xm:craft-guidelines`, not here.

## Deploy SSH connection (`f0587e29`, Nov 2026)

- `bin/ci_ssh_connect` replaces `ssh_retry` & `ssh-agent`: the key is now a file, so anything that relied on the agent
  (forwarding, another job using SSH) breaks. Any other job that connects calls the script itself
- Call it after every step that runs package code, the project's own install & build steps included, so they never
  have the key on disk

## Lando to DDEV (`624886b8`, Nov 2026)

- One change spread over many files (`.ddev/`, the deleted `.lando.yml`, `config/vite.php`, `bin/check*`,
  `.env.example-local`, `.mcp.json`, `README.md`, `AGENTS.md`…). Apply it whole with `/xm:lando-to-ddev`, before or
  after a sync, never in pieces: a project half on each breaks. It also moves the local databases from Lando
- Later DDEV commits (`5ccddb31` UTC, `200b6f15` `ddev vite`, `c5f77c82` checks) sync normally once it's in

## Setup & provisioning scripts in `bin/` (`2e5c2fa9`, `bbb52871`, Oct 2026)

- Older projects have `node_setup.sh`, `provision_site.sh`, `setup_gitlab_ci_vars.sh` & `setup_server.sh` in the root.
  Move them to `bin/` to match, even though `2e5c2fa9` says "new projects only", so later changes to them sync
  cleanly
- `provision_site` & `setup_gitlab_ci_vars` lose `.sh` (they're run as commands, like `bin/check`).
  `node_setup.sh` becomes `setup_node.sh` (`bbb52871`). `setup_server.sh` keeps its name
- Update what calls them: `README.md`, `AGENTS.md` & any deploy notes

## Yarn's age gate (`0e0e01db`, Nov 2025 & `5d5b0cc8`, Aug 2026)

- `npmMinimalAgeGate` in `.yarnrc.yml` makes `yarn up` skip anything published in the last 7 days, so an upgrade can
  look like a no-op. That's intended: `yarn up:bypass <package>` gets past it when a fix can't wait

## Tailwind 4 (`5802092a`, May 2025)

- Run `@tailwindcss/upgrade`, then check its output by hand: it misreads some classes & `@apply` names
- Unlayered CSS now beats utilities: put the project's own CSS in `@layer base` or `@layer components`, as the
  starter does. Utilities then beat components (see `/xm:frontend-guidelines`)
- Most of what breaks is visual & no check catches it: compare before & after screenshots of each page

## Webpack Encore to Vite & Vue 3 (`6aa9256b`, `6a1cc3c5`, Nov 2024)

- One change: take the starter's current `vite.config.mjs` together with the template & CSS changes, never the config
  alone. Swap `clubstudioltd/craft-asset-rev` for `nystudio107/craft-vite` & add `config/vite.php`; every `rev()` in
  the templates becomes `craft.vite.script()`, `craft.vite.asset()` or `craft.vite.register()`
- CSS `url()`s to `/images/…` built fine under Encore but 404 from `/build/` under Vite, with only a build warning:
  make them relative to the CSS file (see `/xm:frontend-guidelines`)
- A Vue app that mounts on `#app` & compiles the page's markup as its template needs the full build
  (`'vue': 'vue/dist/vue.esm-bundler.js'` in `resolve.alias`). The starter doesn't need it, so it isn't in its config;
  without it nothing renders & nothing errors
- Vue 3 condenses whitespace between elements on separate lines, so inline elements run together: fix the markup (a
  literal space where text needs one, gap or margin between elements). Don't set `whitespace: 'preserve'`
- Delete Webpack-era `resolutions` in `package.json` (eg `@achrinza/node-ipc`, `nanoid`): they hold back the Vite
  tree. A project fighting `yarn audit:high` on an Encore tree should make this switch rather than patch packages

## Craft 5 (`16178b43`, Nov 2024)

- First, in their own commits: the latest Craft 4.x, then PHP 8.2+ & MySQL 8.0+ locally & in CI. Redactor has no
  Craft 5 version, so move its fields to CKEditor first (next entry)
- `craft up` doesn't re-export `config/project`: it keeps Craft 4 shapes (`contentColumnType` on every field, lists
  instead of `__assoc__`) & nothing fails. Run `craft project-config/rebuild` as the last step & commit it on its own
- The rebuild changes field types, eg URL fields become Link fields: check the templates that read them, as a Link
  field's value isn't a plain URL (see `/xm:craft-guidelines`)

## Redactor to CKEditor (`58e0e36c`, Jul 2024 & `1f2496b4`, Aug 2024)

- Must land before Craft 5. Retype the fields (including those in Matrix & Neo blocks) in one commit, then delete
  `config/redactor/` & the Redactor plugin in another. The CKEditor toolbar configs come from the starter (see
  `/xm:craft-guidelines`)
