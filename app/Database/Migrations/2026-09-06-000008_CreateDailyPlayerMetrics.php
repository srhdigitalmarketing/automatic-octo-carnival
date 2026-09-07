<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDailyPlayerMetrics extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'visit_date' => ['type' => 'DATE'],
            'impressions' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'play_clicks' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('visit_date', true);
        $this->forge->createTable('traffic_daily_player_metrics', true);
    }

    public function down()
    {
        $this->forge->dropTable('traffic_daily_player_metrics', true);
    }
}
