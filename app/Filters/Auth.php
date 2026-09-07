<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class Auth implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {

        $router = service('router');
        $resolved = $router->controllerName();
        $controller = is_string($resolved) ? strtolower(ltrim($resolved, '\\')) : '';
        $path = strtolower(trim($request->uri->getPath(), '/'));
        $isLogin = $controller === 'app\\controllers\\admin\\login';
        $isAdmin = strpos($controller, 'app\\controllers\\admin\\') === 0
            || $path === 'admin' || strpos($path, 'admin/') === 0;
        if (! $isAdmin && ! $isLogin) { return; }

        if (! $isLogin && ! service('auth')->isLogged()) {
            session()->set('redirect_url', current_url());
            return redirect()->to('/admin_login');
        }

        $method = strtolower($request->getMethod());
        $action = strtolower($router->methodName());
        $mutates = ! in_array($method, ['get', 'head', 'options'], true)
            || preg_match('/^(delete|remove|clear|reset|logout)/', $action)
            || $path === 'admin/logout';
        if ($mutates) {
            $origin = $request->getHeaderLine('Origin');
            $source = $origin !== '' ? $origin : $request->getHeaderLine('Referer');
            // Use configured public origin so TLS-terminating proxies remain supported.
            if (! \App\Libraries\AdminOrigin::matches($source, (string) config('App')->baseURL)) {
                return service('response')->setStatusCode(403)
                    ->setBody('Admin action rejected. Open the admin page on the configured website domain and try again.');
            }
        }
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
