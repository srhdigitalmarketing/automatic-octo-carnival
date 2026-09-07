<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCloudflareR2ApiAccess extends Migration
{
    public function up()
    {
        $this->forge->addColumn('third_party_apis', [
            'r2_account_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
                'after' => 'api_token',
            ],
            'r2_access_key_id' => [
                'type' => 'VARCHAR',
                'constraint' => 128,
                'null' => true,
                'after' => 'r2_account_id',
            ],
            'r2_secret_access_key' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'r2_access_key_id',
            ],
            'r2_bucket' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'r2_secret_access_key',
            ],
            'r2_public_url' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'r2_bucket',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('third_party_apis', [
            'r2_account_id',
            'r2_access_key_id',
            'r2_secret_access_key',
            'r2_bucket',
            'r2_public_url',
        ]);
    }
}
