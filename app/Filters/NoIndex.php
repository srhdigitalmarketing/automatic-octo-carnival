<?php
namespace App\Filters;

use App\Libraries\SiteIndexing;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class NoIndex implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Safe fallback for authentication redirects and exceptions before after().
        service('response')->setHeader('X-Robots-Tag', SiteIndexing::NOINDEX);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $robots = SiteIndexing::forResponse($request, $response);
        // CI 4 sends headers with replace=false. Remove the native bootstrap fallback
        // so Index mode cannot accidentally send both index and noindex headers.
        if (!headers_sent()) header_remove('X-Robots-Tag');
        $response->setHeader('X-Robots-Tag', $robots);
    }
}
