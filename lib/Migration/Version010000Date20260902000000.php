<?php
declare(strict_types=1);

namespace OCA\TeamFolders\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version010000Date20260902000000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();
        $exposure = $schema->getTable('tf_exposure');
        if (!$exposure->hasColumn('is_folder')) {
            $exposure->addColumn('is_folder', Types::SMALLINT, ['notnull' => true, 'default' => 1]);
        }

        if (!$schema->hasTable('tf_share_fact')) {
            $facts = $schema->createTable('tf_share_fact');
            $facts->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
            $facts->addColumn('share_id', Types::STRING, ['notnull' => true, 'length' => 255]);
            $facts->addColumn('storage_id', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
            $facts->addColumn('node_id', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
            $facts->addColumn('mask', Types::SMALLINT, ['notnull' => true, 'unsigned' => true, 'default' => 0]);
            $facts->addColumn('expires_at', Types::BIGINT, ['notnull' => false, 'unsigned' => true]);
            $facts->addColumn('generation', Types::BIGINT, ['notnull' => true, 'unsigned' => true, 'default' => 0]);
            $facts->addColumn('updated_at', Types::BIGINT, ['notnull' => true, 'unsigned' => true, 'default' => 0]);
            $facts->setPrimaryKey(['id']);
            $facts->addUniqueIndex(['share_id'], 'tf_share_id_unique');
            $facts->addIndex(['storage_id', 'node_id'], 'tf_fact_node');
            $facts->addIndex(['generation'], 'tf_fact_generation');
        }

        if (!$schema->hasTable('tf_index_state')) {
            $state = $schema->createTable('tf_index_state');
            $state->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
            $state->addColumn('storage_id', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
            $state->addColumn('status', Types::STRING, ['notnull' => true, 'length' => 16, 'default' => 'pending']);
            $state->addColumn('generation', Types::BIGINT, ['notnull' => true, 'unsigned' => true, 'default' => 0]);
            $state->addColumn('last_success', Types::BIGINT, ['notnull' => true, 'unsigned' => true, 'default' => 0]);
            $state->addColumn('last_error', Types::TEXT, ['notnull' => false, 'length' => 1024]);
            $state->addColumn('updated_at', Types::BIGINT, ['notnull' => true, 'unsigned' => true, 'default' => 0]);
            $state->setPrimaryKey(['id']);
            $state->addUniqueIndex(['storage_id'], 'tf_state_storage');
        }
        return $schema;
    }
}
