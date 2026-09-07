<?php

namespace App\Entities;


class Page extends \CodeIgniter\Entity\Entity
{

    protected $casts = [
        'content' => 'base64'
    ];

    protected $castHandlers = [
        'base64' => \App\Entities\Cast\CastBase64::class
    ];

    public function isPublic(): bool
    {
        return $this->status == 'public';
    }


}