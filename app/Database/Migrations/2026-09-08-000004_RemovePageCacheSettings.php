<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class RemovePageCacheSettings extends Migration
{
    public function up()
    {
        $this->db->table('settings')->whereIn('name', [
            'web_page_cache', 'web_page_cache_types', 'web_page_cache_duration',
        ])->delete();
    }

    public function down()
    {
        // Removed settings had user-specific values; do not re-enable page caching.
    }
}
