<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDailyTrafficVisitors extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'visit_date' => [
                'type' => 'DATE',
            ],
            'visitor_key' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
            ],
            'platform' => [
                'type' => 'VARCHAR',
                'constraint' => 12,
                'default' => 'desktop',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['visit_date', 'visitor_key']);
        $this->forge->addKey(['visit_date', 'platform']);
        $this->forge->createTable('traffic_daily_visitors', true);
    }

    public function down()
    {
        $this->forge->dropTable('traffic_daily_visitors', true);
    }
}
