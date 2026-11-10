# Self-hosted Claude marketplace PoC

Laravel 13 / Livewire 4 starter kit, Pest, one marketplace (`artisan-poc`), one skill-only plugin (`hello-skill`). This **public** repository contains only demonstration content, not credentials or client skills. The token endpoint is an attribution experiment, not DRM: the same demo source is public here and installed skills remain cached after revocation.

**Observed blocker:** Claude Code 2.1.322 cannot add this dumb-HTTP URL: its shallow clone fails with `fatal: dumb http transport does not support shallow capabilities`. Ordinary public HTTPS Git clone succeeds. The PoC preserves the specified transport rather than silently adding smart HTTP. See the evidence and recommendation in [test results](docs/test-results.md). Desktop remains untested.

## Local setup

Requires PHP 8.4+, Composer, Node 22+, and Git. Install Claude Code for manifest validation (`npm install -g @anthropic-ai/claude-code`).

```sh
composer setup
```

Set `POC_ADMIN_EMAIL` and four distinct random passwords in your untracked `.env`: `POC_ADMIN_PASSWORD`, `POC_CLI_PASSWORD`, `POC_MAC_PASSWORD`, `POC_WINDOWS_PASSWORD`. No self-signup is enabled. Then:

```sh
php artisan db:seed
php artisan marketplace:build
php artisan serve
```

Seeded logins: configured admin, `cli@example.invalid`, `mac@example.invalid`, `windows@example.invalid`. The seeder does not reset existing users/passwords. Visit `/plugins` after login; administrators also have `/admin/marketplace`. To run checks: `composer test` (Pint, PHPStan, Pest), `npm run build`, and `claude plugin validate marketplace/`. CI runs these checks with PHP 8.4.

## Git transport and build

`php artisan marketplace:build` validates with Claude Code if installed (validation failure fails the command; absence emits a warning). It copies `marketplace/` into a temporary worktree, makes one SHA-1 orphan commit with fixed identity/date, clones bare, repacks, and runs `update-server-info`. Identical content produces identical commit SHAs. `MARKETPLACE_COMMIT_DATE` defaults to `2026-01-01T00:00:00Z`.

`storage/app/marketplace.git` is an atomically switched symlink to an immutable sibling release directory. `storage/app/marketplace.sha` contains HEAD; the controller reads the release-local SHA to avoid a metadata race. Old directories remain until the image is replaced, allowing in-flight file responses to finish. Build output must stay outside the public webroot. Build and runtime must preserve the same absolute app path and symlinks.

The read-only `/m/{token}/marketplace.git/{path}` route is outside `web` middleware. Laravel starts no sessions or cookies and adds no CSRF challenges, Git backend, or push operation. Cloud's edge may independently set a `__cf_bm` cookie; the successful Git clone did not need a browser challenge. The allowlist is exactly HEAD, refs, pack list, SHA-1 pack/index files, and loose objects. The smart-HTTP discovery query gets plain refs, allowing ordinary Git to fall back to dumb HTTP. Other methods/paths get 404 and are logged, including the raw encoded relative path. Unknown/revoked credentials are classified `rejected`; valid credentials use `check` / `fetch` / `other` by path even on 404. There is no shallow-clone support in dumb HTTP.

Tokens have 256 bits of entropy, a unique SHA-256 lookup hash and a Laravel-encrypted recoverable value. Regeneration revokes all old active tokens for that user inside a transaction. Keep `APP_KEY` stable; losing it prevents redisplaying issued URLs. Browser token pages are no-store/no-referrer. No token value is written to application request rows; path is relative to the credential-bearing repo root. Hosting/edge access logs and client Git configs may still contain full URLs: restrict their access and redact URLs before sharing diagnostics. Failed requests retain the owner when recognizable, including revoked tokens. Unknown credentials have no owner.

The admin page reports observed pack requests, **not proven installs or executions**. “Up to date” means the last requested pack was served from current HEAD; it does not prove the transfer completed. SHA is a version proxy for this one-plugin PoC. Logs include UTC time, IP, user agent, relative path, query, HTTP status, kind, token ID and served SHA. Raw report is capped at the latest 200 rows. No retention/analytics service is added for this small experiment.

## Laravel Cloud deployment

Use the Artisan Build organization, one PoC environment and Cloud's HTTPS subdomain. No custom domain, paid addon, queue worker, or MCP connector is needed. Use the smallest hibernating web instance and an isolated persistent Postgres database; **do not use ephemeral SQLite on Cloud**, which loses accounts, tokens and observations across releases. Do not share Scalpels' database or client marketplace configuration.

Build command:

```sh
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan marketplace:build
```

Git must be available at build time, not at runtime. When Claude CLI is available, the builder validates before publishing; CI always installs and runs the validator. Deploy command: `php artisan migrate --force`. Seed once with `php artisan db:seed --force` after all five `POC_*` values are configured privately in Cloud. Retain `APP_KEY` across deploys. Set `APP_DEBUG=false`, `APP_URL` to the assigned HTTPS domain, `SESSION_SECURE_COOKIE=true`, database session storage, and no bot challenge/CAPTCHA on `/m/*`. Request logs contain personal data; restrict admin/Cloud access.

Before any Claude test, use the CLI test account's personal URL to check `info/refs` returns HTTP 200 and `text/plain`, and run a clean ordinary `git clone` over public HTTPS. Never paste real token URLs into this public repo or issues.

## Publishing 0.2.0

Wait until the Desktop 0.1.0 baseline is recorded. Change both `marketplace/plugins/hello-skill/.claude-plugin/plugin.json` version and the exact reply line in its `skills/hello-skill/SKILL.md` to `0.2.0`. Do **not** add a version to the marketplace entry. Validate, test, commit and deploy. Record deployment time and watch passive checks before manually refreshing clients.

This PoC intentionally rewrites history on each content change. Test `/plugin marketplace update artisan-poc`, plugin update/reinstall if needed, automatic update plus restart, and Desktop refresh/wait up to 24h. If clients reject orphan-history updates, document the exact failure before switching to a persistent bare repo with commits atop the previous HEAD. That fallback requires persistent disk hosting; do not silently change this experiment to smart HTTP.

## Client test plan and decision

See [test results](docs/test-results.md). Use separate accounts for CLI, Mac Desktop (Ed), and Windows Desktop (volunteer pending). Record client version, exact UI error/reply, server row IDs, UTC timestamps, IPs, user agents and paths. Keep unredacted IP evidence in the admin app, not this public repository. If Desktop rejects host/installation, record the exact rejection and stop Desktop expansion. Recommendation then becomes Claude Code only if CLI works, or MCP-delivered playbooks for Desktop. A Git-only test is not evidence Claude can install or execute a skill.

## Scalpels boundary

No Scalpels code, directory submission, domain, database, or marketplace config is changed. Names `artisan-poc` / `hello-skill` are separate. Run CLI tests with a dedicated `CLAUDE_CONFIG_DIR` so installed marketplace settings do not collide. If tokenized URLs are later used for Scalpels, investigate directory caching/proxy fetches, URL exposure in client diagnostics, credential rotation, source redistribution, and marketplace-name collisions first. This PoC does not establish directory-submission compatibility or license enforcement.

The Laravel starter kit is MIT licensed.
