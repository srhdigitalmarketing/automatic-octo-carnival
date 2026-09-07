<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLiveTrafficAndZodeSettings extends Migration
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
            'page' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'embed',
            ],
            'visitor_key' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
            ],
            'last_seen_at' => [
                'type' => 'DATETIME',
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
        $this->forge->addUniqueKey(['page', 'visitor_key']);
        $this->forge->addKey(['page', 'last_seen_at']);
        $this->forge->createTable('live_traffic', true);

        $settings = $this->db->table('settings');
        foreach (['zode_id', 'zode_api_token'] as $name) {
            if ($settings->where('name', $name)->countAllResults() === 0) {
                $this->db->table('settings')->insert([
                    'name' => $name,
                    'value' => '',
                    'data_type' => 'string',
                ]);
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('live_traffic', true);
        $this->db->table('settings')
            ->whereIn('name', ['zode_id', 'zode_api_token'])
            ->delete();
    }
}
