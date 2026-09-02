<?php
declare(strict_types=1);

namespace OCA\TeamFolders\Service;

use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * Transaction boundary for the materialized exposure tree.
 *
 * The production implementation deliberately lives behind this narrow service:
 * 1. classify direct shares for the node;
 * 2. OR children's direct|descendant masks;
 * 3. update the node only when the value changed;
 * 4. repeat upward to the storage root, stopping at the first unchanged parent.
 * Locks must be acquired leaf-to-root and mutations use compare-and-swap generation.
 */
final class Indexer {
    public function __construct(private IDBConnection $db, private LoggerInterface $logger) {}

    public function reindexNodeAndAncestors(int $storageId, int $nodeId): void {
        // Safe scaffold behavior: mark the affected row dirty. The full share-provider
        // classifier is kept out until exercised against an NC34 integration fixture.
        $qb = $this->db->getQueryBuilder();
        $qb->update('tf_exposure')->set('dirty', $qb->createNamedParameter(1))
            ->where($qb->expr()->eq('storage_id', $qb->createNamedParameter($storageId)))
            ->andWhere($qb->expr()->eq('node_id', $qb->createNamedParameter($nodeId)))
            ->executeStatement();
    }

    public function repairDirtyBatch(int $limit): void {
        // Intentionally a no-op until the integration adapter can resolve storage IDs
        // through Nextcloud's internal filecache without crossing the OCP API boundary.
        $this->logger->debug('Team Folders reconciliation tick', ['limit' => $limit]);
    }
}
