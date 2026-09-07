<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Class AdminModel
 * @package App\Models
 * @author John Antonio
 */
class AdminModel extends Model
{
    protected $table            = 'admin';
    protected $returnType       = 'App\Entities\Admin';
    protected $allowedFields    = ['display_name', 'username', 'password'];

    // Dates
    protected $useTimestamps = false;


    //callbacks
    protected $beforeUpdate = ['hashPassword'];

    /**
     * Hash password before update admin password
     * @param array $data
     * @return array
     */
    protected function hashPassword(array $data): array
    {
        if(isset( $data['data']['password'] )){
            $data['data']['password'] = password_hash( $data['data']['password'], PASSWORD_DEFAULT);
        }

        return $data;
    }

    /**
     * Get admin
     * @return array|object|null
     */
    public function getAdmin()
    {
        return $this->where('id', 1)
                    ->first();
    }

}
