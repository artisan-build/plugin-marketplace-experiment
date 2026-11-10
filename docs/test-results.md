# PoC test record

Date: 2026-11-10. Initial plugin: 0.1.0. **Result: ordinary Git works, Claude Code marketplace installation fails on shallow clone.** Desktop remains untested.

Deployment: https://plugin-marketplace-poc-production-rxraqb.laravel.cloud · Artisan Build organization · isolated `Plugin Marketplace PoC` app, `production` environment · PHP 8.4 · hibernating flex-512mb web instance · separate Postgres cluster `plugin-marketplace-poc` with 0.25 CU cap and 60-second suspension. No Scalpels resource was changed. Admin: `ed@artisan.build`. Passwords are private Cloud environment variables: `POC_ADMIN_PASSWORD`, `POC_CLI_PASSWORD`, `POC_MAC_PASSWORD`, `POC_WINDOWS_PASSWORD`; no credential values are in this repository.

## Automated baseline

- Laravel 13 / Livewire starter kit; PHP 8.4, Pest 4.
- `composer test`: 50 passed, 2 skipped (starter-kit self-registration tests, intentionally disabled); Pint and PHPStan pass.
- `npm run build`: passed.
- `claude plugin validate marketplace/`: passed; optional author metadata warning.
- Consecutive source builds: `6641aad89424a3604e56ae1b18cbf31cae35cdc7` both times.
- Pest covers valid plain-text refs including smart-HTTP query, no cookie, private no-cache headers, token encryption, revoked/unknown 404s with owner attribution, traversal/encoded variants, allowlist, pack/index MIME, request classification, deterministic build, file clone, login requirement, signup disabled, token regeneration/isolation, admin authorization and current/behind/never statuses.

## Ordered client plan

| # | Surface | Action | Result / evidence |
|---|---|---|---|
| 0 | Public HTTPS | curl refs, clean Git clone before Claude | PASS, 14:29 UTC: HTTP 200, `text/plain; charset=utf-8`, `Cache-Control: no-cache, private`; clean ordinary Git clone succeeds |
| 1 | Claude Code | Add URL, install `hello-skill@artisan-poc`, say “hello marketplace” | FAIL at add, 14:29:27 UTC, Claude Code 2.1.322: `fatal: dumb http transport does not support shallow capabilities`. No install or skill execution possible through that URL; no false CLI-success claim |
| 2 | Desktop Mac (Ed) | Customize → Plugins → Add → Add marketplace → Add from a repository; install; chat + Cowork | Pending Ed; record Desktop version and exact error/reply |
| 3 | Desktop Windows | Same as Mac | Untested; Ed is seeking an external volunteer (no company Windows machine) |
| 4 | Publish 0.2.0 | Change plugin manifest and reply; deploy, observe passive checks | Held: CLI cannot add 0.1.0; preserve 0.1.0 for Ed's Desktop attempt |
| 5 | CLI update | Marketplace update; auto-update enabled + restart | Blocked by initial marketplace add; orphan-history behavior is not yet tested |
| 6 | Desktop update | Refresh/update control, otherwise wait 24h | Pending Desktop install; record first passive check and update time |
| 7 | Revoke | Regenerate CLI user's URL; trigger update | Live PASS, 14:31:23 UTC: regenerated through authenticated POST; old token 404, new token 200; both attributed to CLI owner. Client/cache portion blocked because no plugin was installed |
| 8 | MCP connector stretch | Separate `hello-connector` plugin | Not started; gated on core tests passing |

For every client attempt, append UTC time, platform/version, exact output, server row IDs and redacted request excerpts. Actual IPs stay in the access-controlled admin app; summarize whether they originated from the client or Anthropic only if verified. Do not infer fetch origin from user-agent alone.

## Server evidence (UTC; credentials and orb IP redacted)

All rows below belong to `cli@example.invalid`; SHA `6641aad89424a3604e56ae1b18cbf31cae35cdc7`.

| Time | Token ID | Path | Query | Kind | HTTP | Agent | IP |
|---|---|---|---|---|---|---|---|
| 14:29:25 | 1 | info/refs | service=git-upload-pack | check | 200 | curl/7.88.1 | orb IP redacted |
| 14:29:25 | 1 | info/refs | service=git-upload-pack | check | 200 | git/2.55.0 | orb IP redacted |
| 14:29:27 | 1 | objects/pack/pack-7c48c0d8ebbb2e04fded69ae82b09f72a88cc455.pack | — | fetch | 200 | git/2.55.0 | orb IP redacted |
| 14:29:27 | 1 | info/refs | service=git-upload-pack | check | 200 | git/2.55.0 | orb IP redacted |
| 14:29:27 | 1 | HEAD | — | other | 200 | git/2.55.0 | orb IP redacted |
| 14:31:23 | 1 (revoked) | info/refs | — | rejected | 404 | curl/7.88.1 | orb IP redacted |
| 14:31:23 | 2 (replacement) | info/refs | — | check | 200 | curl/7.88.1 | orb IP redacted |

The successful ordinary clone also requested loose HEAD object, `objects/info/http-alternates`, and `objects/info/alternates` (404s), then `objects/info/packs`, a pack index and a pack (200s). Claude's failed shallow attempt reached refs and HEAD but no subsequent pack fetch. These requests originated from this controlled orb test; no Anthropic/Desktop fetch origin has been observed. Cloud set an edge `__cf_bm` cookie on the refs response but did not challenge or prevent the clean Git clone. Laravel's endpoint emits no session cookie (Pest verified).

Command output, with only the credential-bearing path redacted:

```text
Adding marketplace…
Failed to add marketplace: Failed to clone marketplace repository:
Cloning into '/tmp/poc-claude/plugins/marketplaces/temp_git_1794320967428_8935c9..clone'...
fatal: dumb http transport does not support shallow capabilities
```

## Recommendation

**Fall back to MCP-delivered playbooks for the staff-delivery goal under the current transport constraint.** The deployed dumb-HTTP service passes ordinary clone, token attribution and live revocation checks, but Claude Code 2.1.322 requires a shallow-capable transport and cannot even add the marketplace, so neither “go” nor “Claude Code only” is supported. Ed's Mac Desktop test is still worthwhile to obtain the exact acceptance/rejection evidence; Windows requires a volunteer. Keep 0.1.0 available and do not claim update timing, rewritten-history compatibility, or cached-skill revocation results without an installed client. A separately approved smart-HTTP experiment could retest the Git approach, but would change this brief's explicit non-goal. No MCP connector or alternative transport has been built. Scalpels remains unchanged; these findings affect only any future plan to serve it via the same dumb-HTTP strategy, not its current directory submission.
