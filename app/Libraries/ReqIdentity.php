<?php

namespace App\Libraries;

/**
 * Class ReqIdentity
 * @package App\Libraries
 * @author John Antonio
 */
class ReqIdentity
{

    /**
     * User IP address
     * @var string|null
     */
    protected $userIp = null;

    /**
     * Unique key to identity
     * @var string|null
     */
    protected $key = null;

    /**
     * User identity token
     * @var string|null
     */
    protected $token = null;

    /**
     * Identity expiring duration
     * @var int
     */
    protected $expire = 60 * 60 * 24; //24 hours


    public function __construct( $key )
    {
        $request = \Config\Services::request();

        $this->userIp = $request->getIPAddress();
        $this->key = $key;

        $this->initToken();
    }

    public function isNew(): bool
    {
        return empty( cache()->get( $this->token ) );
    }

    public function detect()
    {
        cache()->save($this->token, 'user_detected',  $this->expire);
    }

    public function setExpire(int $expire )
    {
        $this->expire = $expire;
    }

    protected function initToken()
    {
        $this->token = md5( $this->userIp . $this->key);
    }

}