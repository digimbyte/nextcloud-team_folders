<?php
declare(strict_types=1);

namespace OCA\TeamFolders\Service;

use OCP\Share\IShare;

final class ShareClassifier {
    public function classify(IShare $share, ?int $now = null): int {
        $now ??= time();
        $expiry = $share->getExpirationDate();
        // NC34's ShareCreatedEvent can carry a valid newly-persisted share whose
        // optional status field has not been hydrated yet. Only an explicit
        // rejected state is invalid.
        $status = null;
        try { $status = $share->getStatus(); } catch (\TypeError) {}
        if ($status === IShare::STATUS_REJECTED || ($expiry !== null && $expiry->getTimestamp() <= $now)) {
            return Exposure::NONE;
        }
        return Exposure::normalize(match ($share->getShareType()) {
            IShare::TYPE_USER, IShare::TYPE_GROUP, IShare::TYPE_CIRCLE,
            IShare::TYPE_GUEST, IShare::TYPE_ROOM, IShare::TYPE_DECK,
            IShare::TYPE_USERGROUP, IShare::TYPE_DECK_USER => Exposure::PEOPLE,
            IShare::TYPE_LINK => Exposure::LINK | ($share->getPassword() === null ? Exposure::PUBLIC : 0),
            IShare::TYPE_EMAIL => Exposure::LINK,
            IShare::TYPE_REMOTE, IShare::TYPE_REMOTE_GROUP, IShare::TYPE_SCIENCEMESH => Exposure::FEDERATED,
            default => Exposure::OTHER,
        });
    }
}
