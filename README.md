# Team Folders

Recursive exposure indicators for Nextcloud 34 Files.

The app distinguishes direct sharing from exposure inherited from anywhere below a folder:

| Badge | Meaning | Nested form |
|---|---|---|
| 👤 | shared with a user/group/circle/team | ghost person |
| 🔗 | link or email share exists | ghost link |
| 🌐 | anonymously public, unprotected link | ghost globe |
| ☁ | federated share | ghost cloud |
| ◆ | future/unknown share provider | ghost diamond |

States are additive. A folder can carry several badges. Public implies link, while password-protected links are not labelled public.

## Status

This is an architecture-first scaffold. The schema, authenticated read endpoint, NC34 Files asset loading, recursive state model, queued/periodic job boundaries, CLI surface, frontend badge adapter, and unit tests are present. The provider classifier and storage crawler are deliberately fail-closed placeholders pending tests against a running NC34 instance; the UI will show no false-safe state from guessed internals.

See [docs/architecture.md](docs/architecture.md) for the invariants and production checklist.

## Development

```bash
composer install
npm install
npm run build
composer test
```

Node 24 is required by `@nextcloud/files` v4. On a Docker host, the complete
isolated build and unit-test pass can be run with:

```bash
sh scripts/test-vm.sh /path/to/team_folders
```

GitHub Actions runs the same frontend checks on Node 24 and the backend suite
across PHP 8.2–8.5. The live NC34 smoke test additionally verifies app
enablement, schema creation, background-job registration, and an authenticated
`/api/v1/indicators` response.

Install this directory as `custom_apps/team_folders`, build assets, enable with `occ app:enable team_folders`, configure Nextcloud system cron, then run `occ team-folders:rebuild`.
