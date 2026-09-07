<?php

if(! function_exists('get_admin_user'))
{
    function get_admin_user()
    {
        return service('auth')->getAdminUser();
    }
}

