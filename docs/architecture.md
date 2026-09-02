# Architecture and invariants

Team Folders is a native Nextcloud app, not a browser extension. Its materialized index is a cache; Nextcloud's share providers remain authoritative.

## State model

Every folder has two additive masks:

- `direct_mask`: exposure applied to that folder itself.
- `descendant_mask`: bitwise OR of `direct_mask | descendant_mask` for all children.

Bits are `PEOPLE`, `LINK`, `PUBLIC`, `FEDERATED`, and `OTHER`. `PUBLIC` implies `LINK`. Password- or identity-protected links are `LINK` only; an anonymous unprotected link is `LINK | PUBLIC`. A direct badge is solid. A descendant-only badge is dashed and translucent (“ghosted”). Direct wins visually when the same bit exists both directly and below.

## Update algorithm

Share create/delete/update, node move/delete, storage mount changes, and group membership changes invalidate an affected node. A queued job recomputes the leaf and walks toward the storage root. Each parent is the OR of its immediate children's complete masks; propagation stops when a parent is unchanged. A generation field supports compare-and-swap updates. Jobs are idempotent and lock leaf-to-root to prevent deadlocks.

The 15-minute reconciliation job repairs missed events in bounded batches. Production requires system cron; AJAX cron does not satisfy “runs all the time.” A full rebuild is exposed through `occ team-folders:rebuild`.

## Security boundary

The index may contain global node facts, but the API accepts a user-visible path and resolves children through that user's `IRootFolder`. It never accepts arbitrary node IDs. This prevents the index from revealing filenames or share states outside the caller's mounts and ACLs.

## NC34 integration risk

NC34 uses `@nextcloud/files` v4, whose registered APIs are major-version scoped. There is currently no stable public API for replacing a row's folder icon. The scaffold therefore adds accessible adjacent badges through a narrow DOM adapter with multiple selectors. Keep the adapter isolated and cover it with an NC34 browser fixture before release. Do not patch Nextcloud core CSS or templates.

## Required production adapters

The scaffold intentionally does not guess across unstable/internal APIs. Before enabling it on real data, add and integration-test:

1. a share-provider classifier using `IManager`, including password/identity semantics;
2. filecache/storage node resolution for background jobs;
3. move, delete, share-update, group/circle membership, and mount invalidation listeners;
4. a batched initial crawler with checkpoints and time budget;
5. PHPUnit integration tests on PostgreSQL, MariaDB, and SQLite, plus an NC34 Playwright UI test.
