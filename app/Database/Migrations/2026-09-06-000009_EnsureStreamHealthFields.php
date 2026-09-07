<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Uses a unique migration version. The original health-fields migration
 * shared a version with CreatePopupAdUnits, so CodeIgniter could skip it.
 */
class EnsureStreamHealthFields extends Migration
{
    private const FIELDS = [
        'host_priority' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 100, 'after' => 'type'],
        'failure_count' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0, 'after' => 'host_priority'],
        'last_checked_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'failure_count'],
        'last_success_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'last_checked_at'],
        'last_failure_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'last_success_at'],
        'last_served_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'last_failure_at'],
        'last_error' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'last_served_at'],
        'upnshare_video_id' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true, 'after' => 'last_error'],
    ];

    public function up()
    {
        if (! $this->db->tableExists('links')) {
            return;
        }

        $existing = array_flip($this->db->getFieldNames('links'));
        $missing = array_diff_key(self::FIELDS, $existing);
        if ($missing !== []) {
            $this->forge->addColumn('links', $missing);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('links')) {
            return;
        }

        $existing = array_flip($this->db->getFieldNames('links'));
        $columns = array_keys(array_intersect_key(self::FIELDS, $existing));
        if ($columns !== []) {
            $this->forge->dropColumn('links', $columns);
        }
    }
}
