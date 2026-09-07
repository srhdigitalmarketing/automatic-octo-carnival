<?php

namespace App\Models;


use CodeIgniter\Model;


class SettingsModel extends Model
{
    protected $table = 'settings';
    protected $allowedFields = ['value'];
    protected $returnType = 'App\Entities\Setting';
    protected $primaryKey = 'name';

    public function getConfig( $name )
    {
        if(empty($name))
            return null;

        return $this->where('name', $name)
                    ->first();
    }





}