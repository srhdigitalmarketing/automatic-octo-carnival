<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAnalyticsDaily extends Migration
{
    public function up()
    {
        $fields = ['visit_date' => ['type' => 'DATE']];
        foreach (['impressions', 'play_clicks', 'unique_visitors', 'desktop', 'mobile', 'tablet', 'other'] as $column) {
            $fields[$column] = ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0];
        }
        $fields['updated_at'] = ['type' => 'DATETIME', 'null' => true];
        $this->forge->addField($fields);
        $this->forge->addKey('visit_date', true);
        $this->forge->createTable('analytics_daily', true);
    }

    public function down()
    {
        $this->forge->dropTable('analytics_daily', true);
    }
}
