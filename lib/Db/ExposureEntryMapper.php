<?php
declare(strict_types=1);

namespace OCA\TeamFolders\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/** @extends QBMapper<ExposureEntry> */
final class ExposureEntryMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'tf_exposure', ExposureEntry::class);
    }

    /** @param list<int> $nodeIds @return array<int, ExposureEntry> keyed by node id */
    public function findForStorageAndNodes(int $storageId, array $nodeIds): array {
        if ($nodeIds === []) return [];
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from('tf_exposure')
            ->where($qb->expr()->eq('storage_id', $qb->createNamedParameter($storageId)))
            ->andWhere($qb->expr()->in('node_id', $qb->createNamedParameter($nodeIds, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)));
        $out = [];
        foreach ($this->findEntities($qb) as $entry) $out[$entry->getNodeId()] = $entry;
        return $out;
    }
}
