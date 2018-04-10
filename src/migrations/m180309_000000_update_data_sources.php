<?php

namespace barrelstrength\sproutforms\migrations;

use craft\db\Migration;
use craft\db\Query;
use barrelstrength\sproutbase\migrations\sproutreports\m180307_042132_craft3_schema_changes as SproutReportsCraft2toCraft3Migration;

/**
 * m180309_000000_update_data_sources migration.
 */
class m180309_000000_update_data_sources extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // First check that the user has Sprout Reports
        $tableSchema = $this->getDb()->schema->getTableSchema('{{%sproutreports_datasources}}');

        if ($tableSchema === null){
            // we don't need this migration
            return true;
        }

        // Run our Sprout Reports migration in Sprout Base
        $migration = new SproutReportsCraft2toCraft3Migration();

        ob_start();
        $migration->safeUp();
        ob_end_clean();

        $oldDataSourceId = 'sproutforms.entries';
        $dataSourceClass = 'barrelstrength\sproutforms\integrations\sproutreports\datasources\EntriesDataSource';

        $query = new Query();

        // See if our old data source exists
        $dataSource = $query->select('*')
            ->from(['{{%sproutreports_datasources}}'])
            ->where(['type' => $oldDataSourceId])
            ->one();

        if ($dataSource === null) {
            // If not, see if our new Data Source exists
            $dataSource = $query->select('*')
                ->from(['{{%sproutreports_datasources}}'])
                ->where(['type' => $dataSourceClass])
                ->one();
        }

        // If we don't have a Data Source record, no need to do anything
        if ($dataSource === null) {
            $this->insert('{{%sproutreports_datasources}}', [
                'type' => $dataSourceClass,
                'allowNew' => 1
            ]);
            $dataSource['id'] = $this->db->getLastInsertID('{{%sproutreports_datasources}}');
            $dataSource['allowNew'] = 1;
        }

        // Update our existing or new Data Source
        $this->update('{{%sproutreports_datasources}}', [
            'type' => $dataSourceClass,
            'allowNew' => $dataSource['allowNew'] ?? 1
        ], [
            'id' => $dataSource['id']
        ], [], false);

        // Update any related dataSourceIds in our Reports table
        $this->update('{{%sproutreports_reports}}', [
            'dataSourceId' => $dataSource['id']
        ], [
            'dataSourceId' => $oldDataSourceId
        ], [], false);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180309_000000_update_data_sources cannot be reverted.\n";
        return false;
    }
}
