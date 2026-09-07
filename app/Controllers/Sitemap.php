<?php
namespace App\Controllers;

class Sitemap extends BaseController
{
    public function index()
    {
        return $this->response->setStatusCode(410)
            ->setHeader('X-Robots-Tag', 'noindex, nofollow, noimageindex, nosnippet')
            ->setContentType('text/plain')->setBody('Sitemap is no longer available.');
    }
}
