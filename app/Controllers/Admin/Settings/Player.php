<?php

namespace App\Controllers\Admin\Settings;

class Player extends BaseSettings
{
    public function index()
    {
        $title = 'Player Settings';

        return view('admin/settings/player', compact('title'));
    }

    public function update()
    {
        if ($this->request->getMethod() !== 'post') {
            return redirect()->back();
        }

        $rules = [
            'player_button_color' => 'required|regex_match[/^#[A-Fa-f0-9]{6}$/]',
            'player_icon_color' => 'required|regex_match[/^#[A-Fa-f0-9]{6}$/]',
            'player_button_style' => 'required|in_list[solid,outline]',
            'player_button_icon' => 'required|in_list[play,play-circle,film,bolt]',
            'player_button_size' => 'required|integer|greater_than_equal_to[48]|less_than_equal_to[140]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }

        $data = $this->request->getPost([
            'player_button_color',
            'player_icon_color',
            'player_button_style',
            'player_button_icon',
            'player_button_size',
        ]);

        foreach ($data as $name => $value) {
            $existing = $this->model->getConfig($name);
            if ($existing === null) {
                db_connect()->table('settings')->insert([
                    'name' => $name,
                    'value' => $value,
                    'data_type' => $name === 'player_button_size' ? 'int' : 'string',
                ]);
                continue;
            }

            db_connect()->table('settings')
                ->where('name', $name)
                ->update(['value' => $value]);
        }

        return redirect()->back()->with('success', 'Player appearance updated successfully.');
    }
}
