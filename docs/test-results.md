# PoC test record

Date: 2026-11-10. Initial plugin: 0.1.0. No production recommendation until actual client observations are recorded.

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
| 0 | Public HTTPS | curl refs, clean Git clone before Claude | Pending deployment |
| 1 | Claude Code | Add URL, install `hello-skill@artisan-poc`, say “hello marketplace” | Pending public HTTPS baseline; Claude Code 2.1.322 installed |
| 2 | Desktop Mac (Ed) | Customize → Plugins → Add → Add marketplace → Add from a repository; install; chat + Cowork | Pending Ed; record Desktop version and exact error/reply |
| 3 | Desktop Windows | Same as Mac | Untested; Ed is seeking an external volunteer (no company Windows machine) |
| 4 | Publish 0.2.0 | Change plugin manifest and reply; deploy, observe passive checks | Deliberately held until 0.1.0 client baseline |
| 5 | CLI update | Marketplace update; auto-update enabled + restart | Pending baseline and 0.2.0; record orphan-history behavior |
| 6 | Desktop update | Refresh/update control, otherwise wait 24h | Pending Desktop install; record first passive check and update time |
| 7 | Revoke | Regenerate CLI user's URL; trigger update | Server rejection/regeneration covered by Pest; real client error/cache behavior pending |
| 8 | MCP connector stretch | Separate `hello-connector` plugin | Not started; gated on core tests passing |

For every client attempt, append UTC time, platform/version, exact output, server row IDs and redacted request excerpts. Actual IPs stay in the access-controlled admin app; summarize whether they originated from the client or Anthropic only if verified. Do not infer fetch origin from user-agent alone.

## Recommendation (provisional)

Continue the bounded experiment, not a production rollout. The server-side contract is validated locally, but no Desktop install/update result has been observed and Windows remains untested. A “go” requires actual Desktop evidence; “Claude Code only” requires real CLI install and skill execution; MCP-delivered playbooks are the Desktop fallback if either Desktop platform rejects the host or install path. Do not claim revocation removes cached content or that a pack request proves installation. Scalpels remains independent and unchanged.
