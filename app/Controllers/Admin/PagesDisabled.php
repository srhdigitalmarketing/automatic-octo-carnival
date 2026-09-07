<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class PagesDisabled extends BaseController
{
    public function index()
    {
        return redirect()->to('/admin/dashboard')
            ->with('info', 'Pages management is disabled. Existing public pages and their data were kept intact.');
    }
}
