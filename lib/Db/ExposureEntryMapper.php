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

    /** @return array{parent_id:int,direct_mask:int,descendant_mask:int}|null */
    public function findRaw(int $storageId, int $nodeId): ?array {
        $qb = $this->db->getQueryBuilder();
        $row = $qb->select('parent_id', 'direct_mask', 'descendant_mask')->from('tf_exposure')
            ->where($qb->expr()->eq('storage_id', $qb->createNamedParameter($storageId)))
            ->andWhere($qb->expr()->eq('node_id', $qb->createNamedParameter($nodeId)))->executeQuery()->fetch();
        return $row === false ? null : ['parent_id' => (int)$row['parent_id'], 'direct_mask' => (int)$row['direct_mask'], 'descendant_mask' => (int)$row['descendant_mask']];
    }

    public function ensure(int $storageId, int $nodeId, int $parentId, bool $isFolder, int $generation, int $now): void {
        $qb = $this->db->getQueryBuilder();
        $affected = $qb->update('tf_exposure')->set('parent_id', $qb->createNamedParameter($parentId))
            ->set('is_folder', $qb->createNamedParameter($isFolder ? 1 : 0))->set('generation', $qb->createNamedParameter($generation))
            ->set('updated_at', $qb->createNamedParameter($now))
            ->where($qb->expr()->eq('storage_id', $qb->createNamedParameter($storageId)))
            ->andWhere($qb->expr()->eq('node_id', $qb->createNamedParameter($nodeId)))->executeStatement();
        if ($affected > 0) return;
        $qb = $this->db->getQueryBuilder();
        $qb->insert('tf_exposure')->values([
            'storage_id' => $qb->createNamedParameter($storageId), 'node_id' => $qb->createNamedParameter($nodeId),
            'parent_id' => $qb->createNamedParameter($parentId), 'direct_mask' => $qb->createNamedParameter(0),
            'descendant_mask' => $qb->createNamedParameter(0), 'dirty' => $qb->createNamedParameter(1),
            'generation' => $qb->createNamedParameter($generation), 'updated_at' => $qb->createNamedParameter($now),
            'is_folder' => $qb->createNamedParameter($isFolder ? 1 : 0),
        ])->executeStatement();
    }

    public function updateMasks(int $storageId, int $nodeId, int $direct, int $descendant, int $now): void {
        $qb = $this->db->getQueryBuilder();
        $qb->update('tf_exposure')->set('direct_mask', $qb->createNamedParameter($direct))
            ->set('descendant_mask', $qb->createNamedParameter($descendant))->set('dirty', $qb->createNamedParameter(0))
            ->set('updated_at', $qb->createNamedParameter($now))
            ->where($qb->expr()->eq('storage_id', $qb->createNamedParameter($storageId)))
            ->andWhere($qb->expr()->eq('node_id', $qb->createNamedParameter($nodeId)))->executeStatement();
    }

    public function childMask(int $storageId, int $parentId): int {
        $qb = $this->db->getQueryBuilder();
        $cursor = $qb->select('direct_mask', 'descendant_mask')->from('tf_exposure')
            ->where($qb->expr()->eq('storage_id', $qb->createNamedParameter($storageId)))
            ->andWhere($qb->expr()->eq('parent_id', $qb->createNamedParameter($parentId)))->executeQuery();
        $mask = 0;
        while (($row = $cursor->fetch()) !== false) $mask |= (int)$row['direct_mask'] | (int)$row['descendant_mask'];
        return $mask;
    }

    public function deleteNode(int $storageId, int $nodeId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete('tf_exposure')->where($qb->expr()->eq('storage_id', $qb->createNamedParameter($storageId)))
            ->andWhere($qb->expr()->eq('node_id', $qb->createNamedParameter($nodeId)))->executeStatement();
    }

    public function count(): int {
        $qb = $this->db->getQueryBuilder();
        return (int)$qb->select($qb->func()->count('*'))->from('tf_exposure')->executeQuery()->fetchOne();
    }
}
