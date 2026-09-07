<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePopupAdUnits extends Migration
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
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'ad_code' => [
                'type' => 'LONGTEXT',
            ],
            'weight' => [
                'type' => 'INT',
                'constraint' => 3,
                'unsigned' => true,
                'default' => 1,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'default' => 'paused',
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
        $this->forge->addKey(['page', 'status']);
        $this->forge->createTable('popup_ad_units', true);
    }

    public function down()
    {
        $this->forge->dropTable('popup_ad_units', true);
    }
}
