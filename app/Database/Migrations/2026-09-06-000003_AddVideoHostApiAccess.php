<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVideoHostApiAccess extends Migration
{
    public function up()
    {
        $this->forge->addColumn('third_party_apis', [
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'custom',
                'after' => 'name',
            ],
            'api_base_url' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'provider',
            ],
            'api_token' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'api_base_url',
            ],
        ]);

        // Preserve previous records, but prevent old movie/series URL templates
        // from being treated as a new host integration.
        $this->db->table('third_party_apis')
            ->where('provider', 'custom')
            ->update(['status' => 'paused']);
    }

    public function down()
    {
        $this->forge->dropColumn('third_party_apis', ['provider', 'api_base_url', 'api_token']);
    }
}
