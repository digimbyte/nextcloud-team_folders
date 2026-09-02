<?php
declare(strict_types=1);

namespace OCA\TeamFolders\Db;

use OCP\IDBConnection;

final class ShareFactMapper {
    public function __construct(private IDBConnection $db) {}

    public function upsert(string $shareId, int $storageId, int $nodeId, int $mask, ?int $expiresAt, int $generation, int $now): void {
        $qb = $this->db->getQueryBuilder();
        $affected = $qb->update('tf_share_fact')
            ->set('storage_id', $qb->createNamedParameter($storageId))->set('node_id', $qb->createNamedParameter($nodeId))
            ->set('mask', $qb->createNamedParameter($mask))->set('expires_at', $qb->createNamedParameter($expiresAt))
            ->set('generation', $qb->createNamedParameter($generation))->set('updated_at', $qb->createNamedParameter($now))
            ->where($qb->expr()->eq('share_id', $qb->createNamedParameter($shareId)))->executeStatement();
        if ($affected > 0) return;
        $qb = $this->db->getQueryBuilder();
        $qb->insert('tf_share_fact')->values([
            'share_id' => $qb->createNamedParameter($shareId), 'storage_id' => $qb->createNamedParameter($storageId),
            'node_id' => $qb->createNamedParameter($nodeId), 'mask' => $qb->createNamedParameter($mask),
            'expires_at' => $qb->createNamedParameter($expiresAt), 'generation' => $qb->createNamedParameter($generation),
            'updated_at' => $qb->createNamedParameter($now),
        ])->executeStatement();
    }

    /** @return array{storage_id:int,node_id:int}|null */
    public function findLocation(string $shareId): ?array {
        $qb = $this->db->getQueryBuilder();
        $row = $qb->select('storage_id', 'node_id')->from('tf_share_fact')
            ->where($qb->expr()->eq('share_id', $qb->createNamedParameter($shareId)))->executeQuery()->fetch();
        return $row === false ? null : ['storage_id' => (int)$row['storage_id'], 'node_id' => (int)$row['node_id']];
    }

    public function delete(string $shareId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete('tf_share_fact')->where($qb->expr()->eq('share_id', $qb->createNamedParameter($shareId)))->executeStatement();
    }

    public function maskForNode(int $storageId, int $nodeId, int $now): int {
        $qb = $this->db->getQueryBuilder();
        $rows = $qb->select('mask')->from('tf_share_fact')
            ->where($qb->expr()->eq('storage_id', $qb->createNamedParameter($storageId)))
            ->andWhere($qb->expr()->eq('node_id', $qb->createNamedParameter($nodeId)))
            ->andWhere($qb->expr()->orX($qb->expr()->isNull('expires_at'), $qb->expr()->gt('expires_at', $qb->createNamedParameter($now))))
            ->executeQuery();
        $mask = 0;
        while (($row = $rows->fetch()) !== false) $mask |= (int)$row['mask'];
        return ExposureMask::known($mask);
    }

    /** @return list<array{storage_id:int,node_id:int}> */
    public function locationsNotInGeneration(int $generation): array {
        $qb = $this->db->getQueryBuilder();
        $cursor = $qb->selectDistinct('storage_id', 'node_id')->from('tf_share_fact')
            ->where($qb->expr()->neq('generation', $qb->createNamedParameter($generation)))->executeQuery();
        $out = [];
        while (($row = $cursor->fetch()) !== false) $out[] = ['storage_id' => (int)$row['storage_id'], 'node_id' => (int)$row['node_id']];
        return $out;
    }

    public function deleteNotInGeneration(int $generation): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete('tf_share_fact')->where($qb->expr()->neq('generation', $qb->createNamedParameter($generation)))->executeStatement();
    }

    public function count(): int {
        $qb = $this->db->getQueryBuilder();
        return (int)$qb->select($qb->func()->count('*'))->from('tf_share_fact')->executeQuery()->fetchOne();
    }
}
