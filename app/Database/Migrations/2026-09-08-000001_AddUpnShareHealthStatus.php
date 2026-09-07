<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class AddUpnShareHealthStatus extends Migration
{
    public function up()
    {
        $fields = array_flip($this->db->getFieldNames('links'));
        foreach (['provider_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true], 'provider_message' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true], 'provider_checked_at' => ['type' => 'DATETIME', 'null' => true], 'health_job_checked_at' => ['type' => 'DATETIME', 'null' => true]] as $name => $definition) {
            if (! isset($fields[$name])) { $this->forge->addColumn('links', [$name => $definition]); }
        }
        if (! in_array('embed_domains', $this->db->getFieldNames('third_party_apis'), true)) {
            $this->forge->addColumn('third_party_apis', ['embed_domains' => ['type' => 'VARCHAR', 'constraint' => 1000, 'default' => '']]);
        }
    }
    public function down()
    {
        $fields = array_intersect(['provider_status','provider_message','provider_checked_at','health_job_checked_at'], $this->db->getFieldNames('links'));
        if ($fields) { $this->forge->dropColumn('links', array_values($fields)); }
        if (in_array('embed_domains', $this->db->getFieldNames('third_party_apis'), true)) { $this->forge->dropColumn('third_party_apis', 'embed_domains'); }
    }
}
