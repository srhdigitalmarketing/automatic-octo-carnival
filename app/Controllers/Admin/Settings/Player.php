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

        // Keep older forms compatible without resetting the saved loading color.
        if ($this->request->getPost('player_loading_color') !== null) {
            $rules['player_loading_color'] = 'required|regex_match[/^#[A-Fa-f0-9]{6}$/]';
        }

        $data = $this->request->getPost(array_keys($rules));
        foreach ($data as $value) {
            if ($value !== null && !is_string($value)) {
                return redirect()->back()->with('errors', ['Player appearance values must be text.']);
            }
        }
        $this->validator = \Config\Services::validation();
        if (! $this->validator->setRules($rules)->run($data)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }

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
