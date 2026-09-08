<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class RemoveStreamHgIntegration extends Migration
{
    public function up()
    {
        $ids = array_column($this->db->table('third_party_apis')->select('id')->where('provider', 'streamhg')->get()->getResultArray(), 'id');
        if ($ids) {
            $this->db->table('links')->whereIn('api_id', $ids)->update(['api_id'=>null]);
            $this->db->table('third_party_apis')->whereIn('id', $ids)->delete();
        }
    }
    public function down() { /* Removed API credentials cannot be restored. */ }
}
