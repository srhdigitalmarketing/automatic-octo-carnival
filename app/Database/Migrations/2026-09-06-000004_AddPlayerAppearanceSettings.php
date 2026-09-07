<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPlayerAppearanceSettings extends Migration
{
    public function up()
    {
        $settings = [
            'player_button_color' => ['value' => '#d28a15', 'data_type' => 'string'],
            'player_icon_color' => ['value' => '#ffffff', 'data_type' => 'string'],
            'player_button_style' => ['value' => 'solid', 'data_type' => 'string'],
            'player_button_icon' => ['value' => 'play', 'data_type' => 'string'],
            'player_button_size' => ['value' => '88', 'data_type' => 'int'],
        ];

        foreach ($settings as $name => $setting) {
            if ($this->db->table('settings')->where('name', $name)->countAllResults() === 0) {
                $this->db->table('settings')->insert([
                    'name' => $name,
                    'value' => $setting['value'],
                    'data_type' => $setting['data_type'],
                ]);
            }
        }
    }

    public function down()
    {
        $this->db->table('settings')->whereIn('name', [
            'player_button_color',
            'player_icon_color',
            'player_button_style',
            'player_button_icon',
            'player_button_size',
        ])->delete();
    }
}
