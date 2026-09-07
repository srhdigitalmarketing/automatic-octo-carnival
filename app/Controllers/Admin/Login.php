<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Login extends BaseController
{
    public function index()
    {
        $title = 'Admin Login';

        //redirect already logged admin to dashboard
        if(service('auth')->isLogged()){
            return redirect()->to('/admin');
        }

        if($this->request->getMethod() == 'post')
        {
            $key = 'admin-login-' . hash('sha256', $this->request->getIPAddress());
            if (! service('throttler')->check($key, 10, 300)) {
                return $this->response->setStatusCode(429)->setHeader('Retry-After', '300')
                    ->setBody('Too many login attempts. Please try again in five minutes.');
            }
            $username = $this->request->getPost('username');
            $password = $this->request->getPost('password');

            if(service('auth')->login($username, $password)){

                //redirect to dashboard
                return redirect()->to('/admin/dashboard');

            }

            return redirect()->back()
                             ->with('error', 'Invalid username or password');

        }

        return view('admin/auth/login', compact('title'));
    }
}
