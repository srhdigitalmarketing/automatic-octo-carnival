<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPopupAdUnitAnalyticsCredentials extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('popup_ad_units')) {
            return;
        }

        $fields = $this->db->getFieldNames('popup_ad_units');

        if (! in_array('zone_id', $fields, true)) {
            $this->forge->addColumn('popup_ad_units', [
                'zone_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                ],
            ]);
        }

        if (! in_array('api_token', $fields, true)) {
            $this->forge->addColumn('popup_ad_units', [
                'api_token' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
            ]);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('popup_ad_units')) {
            return;
        }

        $fields = $this->db->getFieldNames('popup_ad_units');
        $columns = array_values(array_intersect(['zone_id', 'api_token'], $fields));

        if ($columns !== []) {
            $this->forge->dropColumn('popup_ad_units', $columns);
        }
    }
}
