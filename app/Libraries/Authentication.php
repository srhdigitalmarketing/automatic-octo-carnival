<?php

namespace App\Libraries;


use App\Models\AdminModel;


class Authentication
{

    private static $admin;


    public function login($username, $password): bool
    {

        $admin = $this->getAdminUser();

        if($admin === null)
            return false;

        if(! $admin->verifyUsername( $username ))
            return false;

        if(! $admin->verifyPassword( $password ))
            return false;


        $session = session();
        $session->regenerate();
        $session->set('is_logged', 1);

        return true;

    }


    public function getAdminUser()
    {
        if (self::$admin === null) {
            $adminModel = new AdminModel();
            self::$admin = $adminModel->getAdmin();
        }

        return self::$admin;

    }


    public function isLogged(): bool
    {
        return session()->get('is_logged') == 1;
    }




}
