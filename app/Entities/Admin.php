<?php

namespace App\Entities;


class Admin extends \CodeIgniter\Entity\Entity
{

    public function verifyPassword( $password ): bool
    {
        return password_verify($password, $this->password);
    }

    public function verifyUsername( $username ): bool
    {
        return $username == $this->username;
    }


}