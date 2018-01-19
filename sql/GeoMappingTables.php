<?php
/**
 * cBackup GeoMapping Plugin
 * Copyright (C) 2017, Oļegs Čapligins, Imants Černovs, Dmitrijs Galočkins
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

namespace app\modules\plugins\geomapping\sql;

use app\components\PluginTableInstaller;


/**
 * @package app\modules\plugins\geomapping\sql
 */
class GeoMappingTables extends PluginTableInstaller
{

    /**
     * @inheritdoc
     */
    public function install()
    {
        $this->command->createTable('{{%plg_geomapping_geolocation}}', [
            'id'           => $this->integer(11)->notNull().' AUTO_INCREMENT',
            'node_id'      => $this->integer(11)->notNull(),
            'last_query'   => $this->string(255)->notNull(),
            'full_address' => $this->string(255)->notNull(),
            'address_data' => $this->text()->notNull(),
            'latitude'     => $this->decimal(10, 8)->notNull(),
            'longitude'    => $this->decimal(11, 8)->notNull(),
            'created'      => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'modified'     => $this->timestamp()->defaultValue(null),
            'PRIMARY KEY (`id`)'
        ])->execute();


        $this->command->createTable('{{%plg_geomapping_log_geo}}', [
            'id'            => $this->integer(11)->notNull() . ' AUTO_INCREMENT',
            'userid'        => $this->string(128)->null()->defaultValue(NULL),
            'time'          => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'node_id'       => $this->integer(11)->null()->defaultValue(null),
            'severity'      => $this->string(32)->notNull(),
            'action'        => $this->string(45)->null()->defaultValue(null),
            'message'       => $this->text()->notNull(),
            'PRIMARY KEY (`id`)'
        ])->execute();
		
		$this->command->insert('{{%task}}', [
            'name'        => 'geo_mapping',
            'put'         => null,
            'table'       => null,
            'task_type'   => 'yii_console_task',
            'yii_command' => 'plugins/geomapping/run/get-geolocation',
            'protected'   => 1,
            'description' => 'Task for running node geo location collecting via Cron',
        ])->execute();

        $this->command->addForeignKey('fk_plg_geomapping_geolocation1', '{{%plg_geomapping_geolocation}}', 'node_id', '{{%node}}', 'id', 'CASCADE', 'CASCADE')->execute();
        $this->command->addForeignKey('fk_plg_geomapping_log_geo_user1', '{{%plg_geomapping_log_geo}}', 'userid', '{{%user}}', 'userid', 'SET NULL', 'CASCADE')->execute();
        $this->command->addForeignKey('fk_plg_geomapping_log_geo_severity1', '{{%plg_geomapping_log_geo}}', 'severity', '{{%severity}}', 'name', 'RESTRICT', 'CASCADE')->execute();
        $this->command->addForeignKey('fk_plg_geomapping_log_geo_node1', '{{%plg_geomapping_log_geo}}', 'node_id', '{{%node}}', 'id', 'CASCADE', 'CASCADE')->execute();
        $this->command->createIndex('node_id_UNIQUE', '{{%plg_geomapping_geolocation}}', 'node_id', true)->execute();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function update()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function remove()
    {
        $this->command->dropTable('{{%plg_geomapping_geolocation}}')->execute();
        $this->command->dropTable('{{%plg_geomapping_log_geo}}')->execute();
		$this->command->delete('{{%task}}', ['name' => 'geo_mapping'])->execute();
        return true;
    }

}
