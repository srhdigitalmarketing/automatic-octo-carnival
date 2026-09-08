<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class RemoveVideoDailyViews extends Migration
{
    public function up()
    {
        // Requested removal of period rankings; lifetime movies.views is preserved.
        $this->forge->dropTable('video_daily_views', true);
    }
    public function down()
    {
        // Restore schema only. Deleted historical counts cannot be recovered.
        $this->forge->addField(['movie_id'=>['type'=>'INT','unsigned'=>true], 'visit_date'=>['type'=>'DATE'], 'views'=>['type'=>'BIGINT','unsigned'=>true,'default'=>0]]);
        $this->forge->addKey(['movie_id','visit_date'], true);
        $this->forge->addKey('visit_date');
        $this->forge->createTable('video_daily_views', true);
    }
}
