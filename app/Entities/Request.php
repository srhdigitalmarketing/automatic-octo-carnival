<?php

namespace App\Entities;


class Request extends \CodeIgniter\Entity\Entity
{


    public function isImported()
    {
        return $this->status == 'imported';
    }


}