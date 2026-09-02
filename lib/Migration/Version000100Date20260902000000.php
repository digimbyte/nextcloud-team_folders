<?php
declare(strict_types=1);

namespace OCA\TeamFolders\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version000100Date20260902000000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();
        if ($schema->hasTable('tf_exposure')) return null;
        $table = $schema->createTable('tf_exposure');
        $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
        foreach (['storage_id', 'node_id', 'parent_id'] as $name) $table->addColumn($name, Types::BIGINT, ['notnull' => true, 'unsigned' => true, 'default' => 0]);
        foreach (['direct_mask', 'descendant_mask', 'dirty'] as $name) $table->addColumn($name, Types::SMALLINT, ['notnull' => true, 'unsigned' => true, 'default' => $name === 'dirty' ? 1 : 0]);
        $table->addColumn('generation', Types::INTEGER, ['notnull' => true, 'unsigned' => true, 'default' => 0]);
        $table->addColumn('updated_at', Types::BIGINT, ['notnull' => true, 'unsigned' => true, 'default' => 0]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['storage_id', 'node_id'], 'tf_node_unique');
        $table->addIndex(['storage_id', 'parent_id'], 'tf_parent_lookup');
        $table->addIndex(['dirty', 'updated_at'], 'tf_dirty_queue');
        return $schema;
    }
}
