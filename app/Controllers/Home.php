<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        $this->response->setHeader('Cache-Control', 'no-store');
        if (get_config('homepage_active') === false || get_config('homepage_active') === '0' || get_config('homepage_active') === 0) {
            return $this->response->setStatusCode(403)->setBody(view('homepage/forbidden'));
        }
        return view('homepage/storage', [
            'heading'=>get_config('homepage_heading') ?: 'Your videos. One organized space.',
            'intro'=>get_config('homepage_intro') ?: 'Store, organize, and share your video collection from one place.',
            'brand'=>get_config('site_title') ?: 'Video Storage',
        ]);
    }
}
