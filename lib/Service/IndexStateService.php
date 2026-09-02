<?php
declare(strict_types=1);

namespace OCA\TeamFolders\Service;

use OCA\TeamFolders\Db\ExposureEntryMapper;
use OCA\TeamFolders\Db\ShareFactMapper;
use OCP\IDBConnection;

final class IndexStateService {
    public function __construct(private IDBConnection $db, private ShareFactMapper $facts, private ExposureEntryMapper $entries) {}

    public function begin(int $generation): void { $this->setGlobal('rebuilding', $generation, null, false); }
    public function complete(int $generation): void { $this->setGlobal('ready', $generation, null, true); }
    public function fail(int $generation, string $error): void { $this->setGlobal('error', $generation, mb_substr($error, 0, 1000), false); }
    public function touchStorage(int $storageId, string $status, int $generation, ?string $error): void { $this->upsert($storageId, $status, $generation, $error, $status === 'ready'); }

    /** @return array{status:string,generation:int,lastSuccess:int,lastError:?string,updatedAt:int,lagSeconds:int,shares:int,nodes:int} */
    public function status(): array {
        $qb = $this->db->getQueryBuilder();
        $row = $qb->select('*')->from('tf_index_state')->where($qb->expr()->eq('storage_id', $qb->createNamedParameter(0)))->executeQuery()->fetch();
        return [
            'status' => $row === false ? 'pending' : (string)$row['status'],
            'generation' => $row === false ? 0 : (int)$row['generation'],
            'lastSuccess' => $row === false ? 0 : (int)$row['last_success'],
            'lastError' => $row === false || $row['last_error'] === null ? null : (string)$row['last_error'],
            'updatedAt' => $row === false ? 0 : (int)$row['updated_at'],
            'lagSeconds' => $row === false || (int)$row['last_success'] === 0 ? 0 : max(0, time() - (int)$row['last_success']),
            'shares' => $this->facts->count(), 'nodes' => $this->entries->count(),
        ];
    }

    private function setGlobal(string $status, int $generation, ?string $error, bool $success): void { $this->upsert(0, $status, $generation, $error, $success); }
    private function upsert(int $storageId, string $status, int $generation, ?string $error, bool $success): void {
        $now = time();
        $qb = $this->db->getQueryBuilder();
        $qb->update('tf_index_state')->set('status', $qb->createNamedParameter($status))
            ->set('generation', $qb->createNamedParameter($generation))->set('last_error', $qb->createNamedParameter($error))
            ->set('updated_at', $qb->createNamedParameter($now))
            ->set('last_success', $success ? $qb->createNamedParameter($now) : 'last_success')
            ->where($qb->expr()->eq('storage_id', $qb->createNamedParameter($storageId)))->executeStatement();
        $check = $this->db->getQueryBuilder();
        $exists = $check->select('id')->from('tf_index_state')->where($check->expr()->eq('storage_id', $check->createNamedParameter($storageId)))->executeQuery()->fetchOne();
        if ($exists !== false) return;
        $qb = $this->db->getQueryBuilder();
        $qb->insert('tf_index_state')->values([
            'storage_id' => $qb->createNamedParameter($storageId), 'status' => $qb->createNamedParameter($status),
            'generation' => $qb->createNamedParameter($generation), 'last_success' => $qb->createNamedParameter($success ? $now : 0),
            'last_error' => $qb->createNamedParameter($error), 'updated_at' => $qb->createNamedParameter($now),
        ])->executeStatement();
    }
}
